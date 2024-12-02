<?php

namespace App\Services\Client;

use App\Exports\ClientsExport;
use App\Models\Client;
use App\Traits\DivineAPITrait;
use Exception;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ClientService
{
    use DivineAPITrait;

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
                    $clients->whereDate('date_from', $request->date_from);
                }else{
                    $clients->whereBetween('date_from', [$request->date_from, $request->date_to]);
                }
            }elseif($request->filled('date_from')){
                $clients->whereDate('date_from', '>' ,$request->date_from);
            }elseif($request->filled('date_to')){
                $clients->whereDate('date_from', '<' ,$request->date_from);
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

            if ($chartResponse['success'] !== 1) throw new Exception('Erro na API ao gerar Gráfico');

            $client['singChartBs4'] = $chartResponse['data']['base64_image'];

            $client['zodiacSign'] = $this->getZodiacSign($client->day_birth, $client->month_birth);
            $client['zodiacSignDetail'] = $this->getZodiacDetails($client['zodiacSign']);

            return ['status' => true, 'data' => $client];
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
                'short_description' => 'Cheio de coragem e energia, Áries é um líder nato que enfrenta desafios com entusiasmo e determinação.',
                'description' => 'Grandes desafios surgirão, testando sua coragem e capacidade de tomar decisões rápidas. Oportunidades de crescimento aparecerão em momentos inesperados, exigindo que você esteja preparado para agir com determinação. Relacionamentos importantes poderão se fortalecer através de atitudes espontâneas, mas será essencial manter o equilíbrio emocional para evitar conflitos desnecessários. No âmbito profissional, caminhos promissores abrirão portas para conquistas significativas, especialmente se você confiar em sua intuição e iniciativa. Prepare-se para um período de realizações marcantes, mas também de aprendizado.',
            ],
            'Touro' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/touro.webp',
                'short_description' => 'Prático e confiável, Touro valoriza estabilidade e aprecia prazeres simples.',
                'description' => 'Um período de estabilidade trará segurança e conforto, permitindo que você foque em construir bases sólidas para seus objetivos futuros. Novas oportunidades financeiras poderão surgir, recompensando sua paciência e dedicação. Relacionamentos próximos serão fortalecidos, e momentos agradáveis com pessoas queridas ajudarão a recarregar suas energias. No entanto, será importante evitar a teimosia em situações de conflito, buscando sempre o equilíbrio. O futuro promete prazeres simples e conquistas duradouras.',
            ],
            'Gêmeos' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/gemeos.webp',
                'short_description' => 'Comunicativo e curioso, Gêmeos é movido pela busca por conhecimento e novas experiências.',
                'description' => 'O futuro reserva um período de intensa comunicação e aprendizado. Novas ideias e conexões surgirão, permitindo que você expanda seus horizontes e explore caminhos antes inexplorados. Sua curiosidade será uma força motriz, mas será essencial manter o foco para evitar dispersões. Relacionamentos sociais trarão oportunidades valiosas, e viagens ou mudanças de ambiente poderão marcar um novo capítulo em sua jornada. Prepare-se para desafios que testarão sua adaptabilidade, mas que também trarão crescimento pessoal.',
            ],
            'Câncer' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/cancer.webp',
                'short_description' => 'Sensível e protetor, Câncer valoriza relações próximas e cria ambientes acolhedores.',
                'description' => 'Um período de mudanças emocionais profundas permitirá que você se conecte ainda mais com aqueles que ama. O futuro trará oportunidades para fortalecer laços familiares e criar um ambiente seguro e acolhedor. No entanto, será importante equilibrar suas emoções para evitar sobrecargas. Novos projetos pessoais ou profissionais podem surgir, desafiando sua intuição e resiliência. Confie em sua capacidade de adaptação e esteja aberto a mudanças que podem levar a grandes transformações.',
            ],
            'Leão' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/leao.webp',
                'short_description' => 'Carismático e confiante, Leão irradia energia e paixão em tudo o que faz.',
                'description' => 'Um período de brilho pessoal está por vir, trazendo reconhecimento e oportunidades para expressar sua criatividade. Novas responsabilidades podem surgir, exigindo que você demonstre sua capacidade de liderança. Relacionamentos próximos se beneficiarão de sua generosidade e lealdade, mas será importante evitar atitudes impulsivas ou egoístas. No âmbito profissional, o futuro promete conquistas que destacam seu talento e paixão, consolidando seu papel como uma figura inspiradora.',
            ],
            'Virgem' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/virgem.webp',
                'short_description' => 'Meticuloso e analítico, Virgem busca sempre a excelência com sua abordagem prática.',
                'description' => 'Projetos que exigem análise detalhada e organização estão no horizonte, destacando suas habilidades práticas. O futuro trará oportunidades para implementar melhorias em sua rotina, tanto no âmbito pessoal quanto profissional. Relacionamentos se fortalecerão à medida que você demonstrar apoio e lealdade aos que estão ao seu redor. Apesar de sua natureza crítica, será importante reconhecer suas conquistas e se permitir momentos de descanso. Uma fase de estabilidade e crescimento o aguarda.',
            ],
            'Libra' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/libra.webp',
                'short_description' => 'Elegante e diplomático, Libra busca equilíbrio e harmonia em todas as áreas da vida.',
                'description' => 'O futuro promete um período de equilíbrio e harmonia, onde sua habilidade diplomática será essencial para resolver conflitos e construir conexões significativas. Novas parcerias, tanto pessoais quanto profissionais, trarão crescimento mútuo e novas oportunidades. No entanto, será importante tomar decisões firmes para evitar indecisões que possam atrasar seu progresso. Um período de paz e realização está à sua espera, valorizando a beleza e a justiça em todas as áreas de sua vida.',
            ],
            'Escorpião' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/escorpiao.webp',
                'short_description' => 'Intenso e misterioso, Escorpião é movido pela paixão e busca por profundidade emocional.',
                'description' => 'Grandes transformações estão por vir, desafiando você a se aprofundar ainda mais em suas emoções e desejos. Relacionamentos intensos podem trazer mudanças inesperadas, mas também lições valiosas sobre confiança e lealdade. No âmbito profissional, sua determinação abrirá portas para projetos ambiciosos que exigem foco e resiliência. Prepare-se para enfrentar desafios que o fortalecerão, permitindo que você renasça ainda mais forte e determinado.',
            ],
            'Sagitário' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/sagitario.webp',
                'short_description' => 'Aventureiro e otimista, Sagitário é sempre guiado por sua sede de liberdade e aprendizado.',
                'description' => 'Um período de aventuras e descobertas o aguarda, trazendo novas perspectivas e oportunidades para explorar o desconhecido. Viagens ou mudanças de cenário podem abrir portas para crescimento pessoal e aprendizado. No entanto, será importante equilibrar sua sede de liberdade com compromissos importantes. Relacionamentos se beneficiarão de sua energia positiva e entusiasmo, mas certifique-se de dedicar tempo para construir laços mais profundos. O futuro promete expansão e realização em diversas áreas.',
            ],
            'Capricórnio' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/capricornio.webp',
                'short_description' => 'Ambicioso e disciplinado, Capricórnio trabalha incansavelmente para alcançar seus objetivos.',
                'description' => 'O futuro reserva um período de progresso constante, onde sua dedicação e esforço serão recompensados. Novas responsabilidades surgirão, permitindo que você demonstre suas habilidades de liderança e organização. Relacionamentos próximos se fortalecerão à medida que você compartilha suas ambições com os que ama. Apesar do foco no trabalho, será importante encontrar tempo para relaxar e aproveitar os frutos de seu esforço. Um período de crescimento sólido e realização está a caminho.',
            ],
            'Aquário' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/aquario.webp',
                'short_description' => 'Inovador e original, Aquário adora explorar ideias novas e pensar fora da caixa.',
                'description' => 'O futuro promete oportunidades para implementar suas ideias inovadoras e causar impacto positivo em sua comunidade. Novos projetos ou colaborações intelectuais trarão crescimento pessoal e profissional. Relacionamentos se beneficiarão de sua autenticidade e visão de futuro, mas será importante equilibrar sua independência com a dedicação aos outros. Prepare-se para um período de criatividade e realização, onde sua originalidade será uma grande vantagem.',
            ],
            'Peixes' => [
                'icon' => 'https://cloud-statics.estadao.com.br/emais/horoscopo/peixes.webp',
                'short_description' => 'Sonhador e empático, Peixes vive em um mundo de emoções e imaginação.',
                'description' => 'Um período de introspecção e conexão espiritual o aguarda, trazendo clareza sobre seus desejos e aspirações. Relacionamentos se aprofundarão, permitindo que você expresse sua sensibilidade de forma autêntica. No âmbito profissional, sua criatividade será essencial para resolver problemas e abrir novas portas. No entanto, será importante encontrar equilíbrio entre suas emoções e a realidade prática. Um futuro cheio de significado e realização está reservado para você.',
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
}
