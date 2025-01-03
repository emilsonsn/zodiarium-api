<?php

namespace App\Services\Client;

use App\Enums\BrevoListEnum;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Exports\ClientsExport;
use App\Models\City;
use App\Models\Client;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\SaleProduct;
use App\Traits\BrevoTrait;
use App\Traits\DivineAPITrait;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Stichoza\GoogleTranslate\GoogleTranslate;

class ClientService
{
    use DivineAPITrait, BrevoTrait;

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);

            $clients = Client::orderBy('id', 'desc');

            if ($request->filled('search_term')) {
                $clients->where('name', 'LIKE', "%{$request->search_term}%")
                    ->orWhere('email', 'LIKE', "%{$request->search_term}%")
                    ->orWhere('whatsapp', 'LIKE', "%{$request->search_term}%")
                    ->orWhere('ddi', 'LIKE', "%{$request->search_term}%");
            }

            if($request->filled('status')){
                $status = explode(',' ,$request->status);
                $clients->whereIn('status', $status);
            }

            if($request->filled('date_from') && $request->filled('date_to')){
                if($request->date_from === $request->date_to){
                    $clients->whereDate('created_at', $request->date_from);
                }else{
                    $clients->whereBetween('created_at', [$request->date_from, $request->date_to]);
                }
            }elseif($request->filled('date_from')){
                $clients->whereDate('created_at', '>' ,$request->date_from);
            }elseif($request->filled('date_to')){
                $clients->whereDate('created_at', '<' ,$request->date_from);
            }

            $clients = $clients->paginate($perPage);

            return $clients;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function export($request)
    {
        try {
            $status = $request->input('status');
            if (!isset($status)) {
                throw new Exception('Filtro status é obrigatório');
            }
    
            // Substituir caracteres inválidos
            $status = str_replace(['/', '\\'], '-', $status);
    
            $fileName = "clients_{$status}.xlsx";
    
            return Excel::download(new ClientsExport($status), $fileName);
        } catch (Exception $error) {
            return response()->json(['status' => false, 'error' => $error->getMessage()], 400);
        }
    }
    

    public function create($request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'gender' => 'required|string|max:10',
                'address' => 'required|string',
                'day_birth' => 'required|integer|min:1|max:31',
                'month_birth' => 'required|integer|min:1|max:12',
                'year_birth' => 'required|integer',
                'hour_birth' => 'nullable|integer|min:0|max:23',
                'minute_birth' => 'nullable|integer|min:0|max:59',
                'email' => 'nullable|string|email|max:255',
                'ddi' => 'nullable|string|max:5',
                'whatsapp' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:Lead,Client,Partner',
                'client_id' => 'nullable|integer'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);

            $data = $validator->validated();

            $data['hour_birth'] = $data['hour_birth'] ? $data['hour_birth'] : '00';
            $data['minute_birth'] = $data['minute_birth'] ? $data['minute_birth'] : '00';

            $client = Client::updateOrCreate([
                'email' => $data['email'],
            ],$data);

            $data = [
                "full_name" => $client->name,
                "day" => $client->day_birth,
                "month" => $client->month_birth,
                "year" => $client->year_birth,
                "hour" => $client->hour_birth,
                "min" => $client->minute_birth,
                "sec" => 0,
                "gender" => $client->gender,
                "place" => $client->address,
                "lat" => -23.5505,
                "lon" => -46.6333,
                "tzone" => 0,
                "lan" => "en",
                "house_system" => "P",
            ];

            $chartResponse = $this->getNatalChart($data);

            $sunText = $this->getPlanetText($data, 'sun');

            $moonText = $this->getPlanetText($data, 'moon');

            $sunText = $this->translateToPortuguese($sunText['data']['report']);
            $moonText = $this->translateToPortuguese($moonText['data']['report']);

            if ($chartResponse['success'] !== 1) throw new Exception($chartResponse['message'] ?? 'Erro na API ao gerar Gráfico');

            $client['singChartBs4'] = $chartResponse['data']['base64_image'];

            $client['zodiacSign'] = $this->getZodiacSign($client->day_birth, $client->month_birth);
            $client['zodiacSignDetail'] = $this->getZodiacDetails($client['zodiacSign']);
            $client['sunText'] = $sunText;
            $client['moonText'] = $moonText;
            $client['mission'] = $this->getSignText('mission');
            $client['luck'] = $this->getSignText('luck');
            $client['relationships'] = $this->getSignText('relationships');

            $this->addContactInList(BrevoListEnum::Lead->value, $client);

            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function generateReportImmediately($request)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'gender' => 'required|string|max:10',
                'address' => 'required|string',
                'birth' => 'required|string',
                'email' => 'nullable|string|email|max:255',
                'whatsapp' => 'nullable|string|max:20',
                'reports' => 'required|array',
            ];

            $data = $request->all();

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) throw new Exception($validator->errors(), 400);
            
            $birth = Carbon::parse(str_replace('T', ' ',$data['birth']));

            $data['day_birth'] = $birth->day;
            $data['month_birth'] = $birth->month;
            $data['year_birth'] = $birth->year;
            $data['hour_birth'] = $birth->hour;
            $data['minute_birth'] = $birth->minute;

            $client = Client::updateOrCreate([
                'email' => $data['email'],
            ],$data);

            $this->addContactInList(BrevoListEnum::Lead->value, $client);

            $sale = Sale::create([
               'client_id' => $client->id,
               'status' => SaleStatus::Pending->value,
            ]);

            foreach($data['reports'] as $report){
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $report
                ]);
            }

            Payment::create([
                'sale_id' => $sale->id,
                'status' => PaymentStatus::Pending,
                'reference' => '---',
                'entity' => '---',
                'origin_api' => 'Zodiarium',
                'alias' => '',
                'value' => 0,
            ]);

            return ['status' => true, 'data' => $client];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function getCitys($request)
    {
        try {
            $search_term = $request->search_term;            
            
            $citys = City::where('name', 'LIKE', "%$search_term%")
                ->get();

            return ['status' => true, 'data' => $citys];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function getClientZodiacSing($request)
    {
        try {
            $day_birth = $request->day_birth;
            $month_birth = $request->month_birth;
            
            $data = [];

            $data['zodiacSign'] = $this->getZodiacSign($day_birth, $month_birth);
            $data['zodiacSignDetail'] = $this->getZodiacDetails($data['zodiacSign']);

            return ['status' => true, 'data' => $data];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function update($request, $user_id)
    {
        try {
            $rules = [
                'name' => 'required|string|max:255',
                'gender' => 'required|string|max:10',
                'address' => 'required|string',
                'day_birth' => 'required|integer|min:1|max:31',
                'month_birth' => 'required|integer|min:1|max:12',
                'year_birth' => 'required|integer',
                'hour_birth' => 'nullable|integer|min:0|max:23',
                'minute_birth' => 'nullable|integer|min:0|max:59',
                'email' => 'nullable|string|email|max:255|unique:clients,email,' . $user_id,
                'ddi' => 'nullable|string|max:5',
                'whatsapp' => 'nullable|string|max:20',
                'status' => 'nullable|string|in:Lead,Client',
                'client_id' => 'nullable|integer'
            ];

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) throw new Exception($validator->errors());

            $clientToUpdate = Client::find($user_id);

            if (!$clientToUpdate) throw new Exception('Cliente não encontrado');

            $clientToUpdate->update($validator->validated());

            return ['status' => true, 'data' => $clientToUpdate];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    public function delete($id)
    {
        try {
            $client = Client::find($id);

            if (!$client) throw new Exception('Cliente não encontrado');

            $clientName = $client->name;
            $client->delete();

            return ['status' => true, 'data' => $clientName];
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
        }
    }

    private function getZodiacSign($day, $month)
    {
        $zodiacSigns = [
            ['name' => 'Capricórnio', 'start' => '12-22', 'end' => '01-19'],
            ['name' => 'Aquário', 'start' => '01-20', 'end' => '02-18'],
            ['name' => 'Peixes', 'start' => '02-19', 'end' => '03-20'],
            ['name' => 'Áries', 'start' => '03-21', 'end' => '04-19'],
            ['name' => 'Touro', 'start' => '04-20', 'end' => '05-20'],
            ['name' => 'Gêmeos', 'start' => '05-21', 'end' => '06-20'],
            ['name' => 'Câncer', 'start' => '06-21', 'end' => '07-22'],
            ['name' => 'Leão', 'start' => '07-23', 'end' => '08-22'],
            ['name' => 'Virgem', 'start' => '08-23', 'end' => '09-22'],
            ['name' => 'Libra', 'start' => '09-23', 'end' => '10-22'],
            ['name' => 'Escorpião', 'start' => '10-23', 'end' => '11-21'],
            ['name' => 'Sagitário', 'start' => '11-22', 'end' => '12-21'],
        ];

        $year = date('Y');
        $currentDate = strtotime("$year-$month-$day");

        foreach ($zodiacSigns as $zodiac) {
            $startDate = strtotime("$year-{$zodiac['start']}");
            $endDate = strtotime("$year-{$zodiac['end']}");

            if ($endDate < $startDate) {
                if ($currentDate >= strtotime("{$year}-01-01") && $currentDate <= $endDate) {
                    $startDate = strtotime(($year - 1) . "-{$zodiac['start']}");
                } else {
                    $endDate = strtotime(($year + 1) . "-{$zodiac['end']}");
                }
            }

            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                return $zodiac['name'];
            }
        }

        return null;
    }

    
    private function getZodiacDetails($signo)
    {
        $signos = [
            'Áries' => [
                'icon' => 'https://cloudfront-us-east-1.images.arcpublishing.com/estadao/PFMW5VPZTRBGRCF6PGJSPHN2DU.png',
                'short_description' => 'Cheio de coragem e energia, Carneiro é um líder nato que enfrenta desafios com entusiasmo e determinação.',
                'description' => 'Grandes desafios surgirão, testando a sua coragem e capacidade de tomar decisões rápidas. Oportunidades de crescimento aparecerão em momentos inesperados, exigindo que esteja preparado para agir com determinação. Relacionamentos importantes poderão fortalecer-se através de atitudes espontâneas, mas será essencial manter o equilíbrio emocional para evitar conflitos desnecessários. No âmbito profissional, caminhos promissores abrirão portas para conquistas significativas, especialmente se confiar na sua intuição e iniciativa. Prepare-se para um período de realizações marcantes, mas também de aprendizado.',
            ],
            'Touro' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/touro.webp',
                'short_description' => 'Prático e confiável, Touro valoriza estabilidade e aprecia prazeres simples.',
                'description' => 'Um período de estabilidade trará segurança e conforto, permitindo que se concentre em construir bases sólidas para os seus objetivos futuros. Novas oportunidades financeiras poderão surgir, recompensando a sua paciência e dedicação. Relacionamentos próximos serão fortalecidos, e momentos agradáveis com pessoas queridas ajudarão a recarregar as suas energias. No entanto, será importante evitar a teimosia em situações de conflito, procurando sempre o equilíbrio. O futuro promete prazeres simples e conquistas duradouras.',
            ],
            'Gêmeos' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/gemeos.webp',
                'short_description' => 'Comunicativo e curioso, Gémeos é movido pela busca por conhecimento e novas experiências.',
                'description' => 'O futuro reserva um período de intensa comunicação e aprendizado. Novas ideias e conexões surgirão, permitindo que expanda os seus horizontes e explore caminhos antes inexplorados. A sua curiosidade será uma força motriz, mas será essencial manter o foco para evitar dispersões. Relacionamentos sociais trarão oportunidades valiosas, e viagens ou mudanças de ambiente poderão marcar um novo capítulo na sua jornada. Prepare-se para desafios que testarão a sua adaptabilidade, mas que também trarão crescimento pessoal.',
            ],
            'Câncer' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/cancer.webp',
                'short_description' => 'Sensível e protetor, Caranguejo valoriza relações próximas e cria ambientes acolhedores.',
                'description' => 'Um período de mudanças emocionais profundas permitirá que se conecte ainda mais com aqueles que ama. O futuro trará oportunidades para fortalecer laços familiares e criar um ambiente seguro e acolhedor. No entanto, será importante equilibrar as suas emoções para evitar sobrecargas. Novos projetos pessoais ou profissionais podem surgir, desafiando a sua intuição e resiliência. Confie na sua capacidade de adaptação e esteja aberto a mudanças que podem levar a grandes transformações.',
            ],
            'Leão' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/leao.webp',
                'short_description' => 'Carismático e confiante, Leão irradia energia e paixão em tudo o que faz.',
                'description' => 'Um período de brilho pessoal está por vir, trazendo reconhecimento e oportunidades para expressar a sua criatividade. Novas responsabilidades podem surgir, exigindo que demonstre a sua capacidade de liderança. Relacionamentos próximos beneficiar-se-ão da sua generosidade e lealdade, mas será importante evitar atitudes impulsivas ou egoístas. No âmbito profissional, o futuro promete conquistas que destacam o seu talento e paixão, consolidando o seu papel como uma figura inspiradora.',
            ],
            'Virgem' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/virgem.webp',
                'short_description' => 'Meticuloso e analítico, Virgem busca sempre a excelência com a sua abordagem prática.',
                'description' => 'Projetos que exigem análise detalhada e organização estão no horizonte, destacando as suas habilidades práticas. O futuro trará oportunidades para implementar melhorias na sua rotina, tanto no âmbito pessoal quanto profissional. Relacionamentos fortalecer-se-ão à medida que demonstrar apoio e lealdade aos que estão ao seu redor. Apesar da sua natureza crítica, será importante reconhecer as suas conquistas e permitir-se momentos de descanso. Uma fase de estabilidade e crescimento aguarda-o.',
            ],
            'Libra' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/libra.webp',
                'short_description' => 'Elegante e diplomático, Balança busca equilíbrio e harmonia em todas as áreas da vida.',
                'description' => 'O futuro promete um período de equilíbrio e harmonia, onde a sua habilidade diplomática será essencial para resolver conflitos e construir conexões significativas. Novas parcerias, tanto pessoais quanto profissionais, trarão crescimento mútuo e novas oportunidades. No entanto, será importante tomar decisões firmes para evitar indecisões que possam atrasar o seu progresso. Um período de paz e realização está à sua espera, valorizando a beleza e a justiça em todas as áreas da sua vida.',
            ],
            'Escorpião' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/escorpiao.webp',
                'short_description' => 'Intenso e misterioso, Escorpião é movido pela paixão e busca por profundidade emocional.',
                'description' => 'Grandes transformações estão por vir, desafiando-o a aprofundar-se ainda mais nas suas emoções e desejos. Relacionamentos intensos podem trazer mudanças inesperadas, mas também lições valiosas sobre confiança e lealdade. No âmbito profissional, a sua determinação abrirá portas para projetos ambiciosos que exigem foco e resiliência. Prepare-se para enfrentar desafios que o fortalecerão, permitindo que renasça ainda mais forte e determinado.',
            ],
            'Sagitário' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/sagitario.webp',
                'short_description' => 'Aventureiro e otimista, Sagitário é sempre guiado pela sua sede de liberdade e aprendizado.',
                'description' => 'Um período de aventuras e descobertas aguarda-o, trazendo novas perspetivas e oportunidades para explorar o desconhecido. Viagens ou mudanças de cenário podem abrir portas para crescimento pessoal e aprendizado. No entanto, será importante equilibrar a sua sede de liberdade com compromissos importantes. Relacionamentos beneficiar-se-ão da sua energia positiva e entusiasmo, mas certifique-se de dedicar tempo para construir laços mais profundos. O futuro promete expansão e realização em diversas áreas.',
            ],
            'Capricórnio' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/capricornio.webp',
                'short_description' => 'Ambicioso e disciplinado, Capricórnio trabalha incansavelmente para alcançar os seus objetivos.',
                'description' => 'O futuro reserva um período de progresso constante, onde a sua dedicação e esforço serão recompensados. Novas responsabilidades surgirão, permitindo que demonstre as suas habilidades de liderança e organização. Relacionamentos próximos fortalecer-se-ão à medida que partilha as suas ambições com os que ama. Apesar do foco no trabalho, será importante encontrar tempo para relaxar e aproveitar os frutos do seu esforço. Um período de crescimento sólido e realização está a caminho.',
            ],
            'Aquário' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/aquario.webp',
                'short_description' => 'Inovador e original, Aquário adora explorar ideias novas e pensar fora da caixa.',
                'description' => 'O futuro promete oportunidades para implementar as suas ideias inovadoras e causar impacto positivo na sua comunidade. Novos projetos ou colaborações intelectuais trarão crescimento pessoal e profissional. Relacionamentos beneficiar-se-ão da sua autenticidade e visão de futuro, mas será importante equilibrar a sua independência com a dedicação aos outros. Prepare-se para um período de criatividade e realização, onde a sua originalidade será uma grande vantagem.',
            ],
            'Peixes' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/peixes.webp',
                'short_description' => 'Sonhador e empático, Peixes vive num mundo de emoções e imaginação.',
                'description' => 'Um período de introspeção e conexão espiritual aguarda-o, trazendo clareza sobre os seus desejos e aspirações. Relacionamentos aprofundar-se-ão, permitindo que expresse a sua sensibilidade de forma autêntica. No âmbito profissional, a sua criatividade será essencial para resolver problemas e abrir novas portas. No entanto, será importante encontrar equilíbrio entre as suas emoções e a realidade prática. Um futuro cheio de significado e realização está reservado para si.',
            ],
        ];
    
        $signo = ucfirst(strtolower($signo));
    
        if (array_key_exists($signo, $signos)) {
            return $signos[$signo];
        } else {
            return [
                'icon' => 'https://image.flaticon.com/icons/png/512/616/616450.png',
                'short_description' => 'Signo não encontrado. Por favor, verifique a data de nascimento fornecida.',
                'description' => 'O futuro está cheio de possibilidades, mas é importante refletir sobre suas escolhas e criar um caminho alinhado com seus objetivos e desejos.',
            ];
        }
    }    

    private function translateToPortuguese(string $text): string
    {
        try {
            $translator = new GoogleTranslate('pt'); // Define o idioma de destino como português
            $translator->setSource('en'); // Define o idioma de origem como inglês
            return $translator->translate($text);
        } catch (\Exception $e) {
            return 'Erro ao traduzir: ' . $e->getMessage();
        }
    }

    private function getSignText(string $type): string
    {
        $texts = [
            'mission' => "Carrega consigo uma energia única, que influencia as suas escolhas e orienta os caminhos que percorre. A sua missão está intimamente ligada ao desenvolvimento pessoal e ao impacto que pode causar à sua volta. A vida coloca constantemente desafios no seu percurso, não como obstáculos, mas como lições desenhadas para o seu crescimento.

    Sente dentro de si uma vontade de fazer mais, de ser mais, e de encontrar um propósito maior que vá além das necessidades do quotidiano. Essa inquietação é um sinal de que está destinado a algo especial. A sua jornada envolve o equilíbrio entre os seus desejos pessoais e as contribuições que faz ao mundo.

    Talvez já tenha percebido que ajudar outras pessoas, partilhar conhecimento ou simplesmente inspirar quem está ao seu redor são partes fundamentais da sua missão. Mesmo nos momentos de dúvida, lembre-se de que cada passo, por menor que pareça, está alinhado com o propósito que a sua alma escolheu antes de estar aqui.

    A sua missão de vida não é um destino fixo, mas um processo contínuo. Confie no seu potencial e esteja aberto às transformações que o universo lhe apresenta. O seu brilho único ilumina o caminho para si e para aqueles que têm a sorte de cruzar a sua jornada.",
            'luck' => "A sorte é um elemento intrigante na sua vida, muitas vezes parecendo surgir de forma inesperada, como um presente do universo. No entanto, é importante lembrar que a sorte não é apenas fruto do acaso, mas também uma combinação da sua atitude, das escolhas que faz e das oportunidades que decide agarrar.

    Na sua jornada, existem momentos em que tudo parece alinhar-se de forma mágica, como se o destino conspirasse a seu favor. Esses momentos não são meras coincidências; são o reflexo da sua preparação, da sua resiliência e da energia que emana para o mundo. Quanto mais acreditar no seu valor e estiver disposto a arriscar, mais portas se abrirão para si.

    Mesmo nos períodos em que a sorte parecer distante, lembre-se de que ela muitas vezes se manifesta de formas subtis, preparando o terreno para algo maior. Pequenos sinais, encontros inesperados ou até contratempos podem ser parte de um plano maior que o conduz a novas oportunidades.

    Confie que a sua sorte está sempre em movimento, aguardando o momento certo para se revelar. Cultive uma mente aberta e otimista, pois a sorte tem uma forma especial de sorrir para quem acredita no seu potencial e segue em frente com coragem e determinação.",
            'relationships' => "Os relacionamentos são uma parte essencial da vida, moldando quem somos e ajudando-nos a crescer de maneiras inesperadas. Seja numa relação amorosa, familiar ou de amizade, cada conexão traz consigo aprendizagens, desafios e momentos de profunda partilha.

    Talvez já tenha sentido que, por vezes, a comunicação parece ser um obstáculo ou que as suas expectativas nem sempre são correspondidas. Estes momentos não devem ser vistos como falhas, mas como oportunidades para fortalecer os laços e compreender melhor a dinâmica entre si e a outra pessoa.

    Um relacionamento saudável baseia-se no equilíbrio — entre dar e receber, entre escutar e ser ouvido, e entre respeitar os limites do outro e expressar as suas próprias necessidades. Não há conexões perfeitas, mas há aquelas que valem a pena cultivar e cuidar, mesmo perante dificuldades.

    Lembre-se de que o amor, em qualquer forma, é um caminho de construção mútua. O seu papel num relacionamento não é apenas encontrar alguém que o complemente, mas também alguém com quem possa criar algo maior, baseado em confiança, respeito e partilha.

    Acredite no seu valor e na sua capacidade de amar e ser amado. Mesmo nas fases mais desafiantes, cada passo no caminho dos relacionamentos traz-lhe mais perto de uma conexão que enriqueça verdadeiramente a sua vida."
        ];

        return $texts[$type] ?? "Texto não encontrado para o tipo especificado.";
    }

}
