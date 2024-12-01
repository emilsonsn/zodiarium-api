<?php

namespace App\Services\Client;

use App\Models\Client;
use App\Traits\DivineAPITrait;
use Exception;
use Illuminate\Support\Facades\Validator;

class ClientService
{
    use DivineAPITrait;

    public function search($request)
    {
        try {
            $perPage = $request->input('take', 10);
            $search_term = $request->search_term;

            $clients = Client::orderBy('id', 'desc');

            if ($request->filled('search_term')) {
                $clients->where('name', 'LIKE', "%{$search_term}%")
                    ->orWhere('email', 'LIKE', "%{$search_term}%")
                    ->orWhere('whatsapp', 'LIKE', "%{$search_term}%")
                    ->orWhere('ddi', 'LIKE', "%{$search_term}%");
            }

            if($request->filled('status')){
                $status = explode(',' ,$request->status);
                $clients->whereIn('status', $status);
            }

            $clients = $clients->paginate($perPage);

            return $clients;
        } catch (Exception $error) {
            return ['status' => false, 'error' => $error->getMessage(), 'statusCode' => 400];
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

        foreach ($zodiacSigns as $zodiac) {
            $startDate = strtotime($zodiac['start']);
            $endDate = strtotime($zodiac['end']);
            $currentDate = strtotime("$month-$day");

            // Caso o signo seja entre dezembro e janeiro
            if ($zodiac['start'] === '12-22' && $month === 1) {
                $startDate = strtotime('12-22 last year');
            } elseif ($zodiac['end'] === '01-19' && $month === 12) {
                $endDate = strtotime('01-19 next year');
            }

            if ($currentDate >= $startDate && $currentDate <= $endDate) {
                return $zodiac['name'];
            }
        }

        throw new Exception('Data inválida para calcular o signo');
    }

    private function getZodiacDetails($signo)
    {
        $signos = [
            'Áries' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47290.png',
                'descricao' => 'Áries, o primeiro signo do zodíaco, é regido por Marte, o planeta da ação e da guerra. Representa energia, iniciativa e liderança, sendo conhecido por sua coragem e determinação. Os arianos são impulsivos e cheios de entusiasmo, sempre prontos para enfrentar novos desafios e explorar territórios desconhecidos. Essa energia vibrante muitas vezes os leva a assumir papéis de liderança, pois possuem uma natureza pioneira e não têm medo de correr riscos. Apesar de sua força, podem ser considerados impacientes e precisam aprender a canalizar sua energia de maneira produtiva. Relacionamentos com Áries são intensos e apaixonados, mas podem ser tumultuados devido à sua impetuosidade.'
            ],
            'Touro' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47414.png',
                'descricao' => 'Touro, regido por Vênus, o planeta da beleza e do amor, é um signo de terra profundamente enraizado na estabilidade, praticidade e sensualidade. Taurinos valorizam segurança, conforto material e experiências sensoriais. Eles são conhecidos por sua lealdade e determinação, e uma vez que se comprometem com algo, trabalham diligentemente para alcançar seus objetivos. Embora possam ser teimosos, essa característica também reflete sua incrível resiliência. Touro é também um signo associado ao prazer — eles apreciam boa comida, arte e ambientes tranquilos. Nos relacionamentos, são carinhosos e leais, mas podem ser possessivos e protetores.'
            ],
            'Gêmeos' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47271.png',
                'descricao' => 'Gêmeos, regido por Mercúrio, é um signo de ar associado à adaptabilidade, comunicação e inteligência. Os geminianos possuem uma mente curiosa e estão sempre em busca de novos conhecimentos e experiências. São excelentes comunicadores, capazes de se expressar de forma envolvente e cativante. No entanto, essa natureza multifacetada pode fazê-los parecer inconstantes ou superficiais. Gêmeos tem uma incrível capacidade de se adaptar a mudanças, tornando-os companheiros sociais encantadores. Em relacionamentos, valorizam a troca intelectual e a liberdade pessoal, sendo parceiros dinâmicos e versáteis.'
            ],
            'Câncer' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47412.png',
                'descricao' => 'Câncer, regido pela Lua, é um signo de água que simboliza emoção, intuição e cuidado. Cancerianos são profundamente ligados às suas emoções e possuem uma natureza protetora. Eles se destacam por sua capacidade de criar ambientes acolhedores e seguros para aqueles que amam. No entanto, podem ser muito sensíveis e propensos a mudanças de humor devido à sua conexão com a Lua. Valorizam a família e as tradições, e muitas vezes encontram alegria em cuidar dos outros. Em relacionamentos, são leais e dedicados, mas precisam de parceiros que entendam sua natureza emocional e intuitiva.'
            ],
            'Leão' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47337.png',
                'descricao' => 'Leão, regido pelo Sol, é um signo de fogo associado à criatividade, paixão e generosidade. Leoninos são líderes naturais, irradiando confiança e entusiasmo em tudo o que fazem. Sua personalidade magnética atrai pessoas, e eles prosperam em situações que lhes permitem brilhar. No entanto, podem ser percebidos como arrogantes ou egocêntricos, especialmente se sentirem que não estão recebendo a atenção que desejam. Apesar disso, Leão é incrivelmente generoso e leal com aqueles que ama. Em relacionamentos, são apaixonados e românticos, buscando parceiros que valorizem sua energia e criatividade.'
            ],
            'Virgem' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47148.png',
                'descricao' => 'Virgem, regido por Mercúrio, é um signo de terra caracterizado pela análise, perfeccionismo e serviço. Virginianos têm uma habilidade excepcional de perceber detalhes que passam despercebidos por outros. São trabalhadores dedicados, sempre buscando aprimorar-se e contribuir de forma prática. Apesar de sua natureza crítica, isso reflete seu desejo de alcançar a excelência. Nos relacionamentos, são leais e prestativos, mas podem ser reservados emocionalmente. Virgem aprecia a ordem e se sente mais confortável em ambientes estruturados, onde pode exercer sua mente analítica.'
            ],
            'Libra' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47117.png',
                'descricao' => 'Libra, regido por Vênus, é um signo de ar que representa equilíbrio, harmonia e diplomacia. Librianos buscam justiça e valorizam os relacionamentos interpessoais. Possuem um forte senso estético e frequentemente são atraídos por arte, beleza e cultura. Embora possam ser indecisos, isso geralmente se deve ao desejo de considerar todos os lados de uma situação. Em relacionamentos, são românticos e encantadores, sempre buscando criar conexões significativas. Libra também é conhecido por sua habilidade de mediar conflitos, tornando-os excelentes negociadores.'
            ],
            'Escorpião' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47128.png',
                'descricao' => 'Escorpião, regido por Plutão e Marte, é um signo de água associado à intensidade, transformação e mistério. Escorpianos são profundos e muitas vezes magnetizam as pessoas com sua aura enigmática. Possuem uma determinação inabalável e são incrivelmente leais. Apesar disso, podem ser possessivos e ciumentos, especialmente em relacionamentos. Escorpião tem uma capacidade única de se regenerar após desafios, tornando-os incrivelmente resilientes. Eles buscam a verdade em tudo e não têm medo de explorar as profundezas emocionais e espirituais.'
            ],
            'Sagitário' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/41/41478.png',
                'descricao' => 'Sagitário, regido por Júpiter, é um signo de fogo que simboliza aventura, liberdade e otimismo. Sagitarianos são exploradores naturais, sempre em busca de conhecimento e experiências que ampliem seus horizontes. Eles possuem uma natureza filosófica e adoram compartilhar suas ideias com os outros. No entanto, podem ser impacientes ou excessivamente otimistas. Em relacionamentos, buscam parceiros que compartilhem seu amor pela liberdade e pela descoberta. Sagitário é conhecido por seu entusiasmo contagiante e capacidade de inspirar os outros.'
            ],
            'Capricórnio' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/1885/1885325.png',
                'descricao' => 'Capricórnio, regido por Saturno, é um signo de terra associado à ambição, disciplina e responsabilidade. Capricornianos são altamente determinados e trabalham incansavelmente para alcançar seus objetivos. Embora possam ser percebidos como sérios, possuem um senso de humor seco e uma natureza leal. Em relacionamentos, são parceiros confiáveis e comprometidos, mas podem ser cautelosos em demonstrar emoções. Capricórnio valoriza a tradição e a estabilidade, buscando construir uma vida sólida e segura para si e para os outros.'
            ],
            'Aquário' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47246.png',
                'descricao' => 'Aquário, regido por Urano, é um signo de ar que representa inovação, originalidade e independência. Aquarianos são visionários e frequentemente pensam fora da caixa, buscando soluções criativas para problemas. Valorizam a liberdade e a individualidade, o que pode torná-los às vezes distantes emocionalmente. Em relacionamentos, apreciam conexões intelectuais e companheiros que compartilhem sua visão de mundo. Aquário é também um signo humanitário, comprometido em fazer do mundo um lugar melhor.'
            ],
            'Peixes' => [
                'icone' => 'https://cdn-icons-png.flaticon.com/512/47/47160.png',
                'descricao' => 'Peixes, regido por Netuno, é um signo de água caracterizado pela empatia, sensibilidade e imaginação. Piscianos são sonhadores naturais, muitas vezes conectados ao mundo espiritual. Possuem uma intuição aguçada e são profundamente compassivos, sempre dispostos a ajudar os outros. No entanto, podem se perder em suas emoções e precisam de momentos de solidão para recarregar suas energias. Em relacionamentos, são românticos e dedicados, mas podem ser excessivamente idealistas. Peixes é um signo que valoriza a conexão emocional acima de tudo.'
            ],
        ];        

        $signo = ucfirst(strtolower($signo));

        if (array_key_exists($signo, $signos)) {
            return $signos[$signo];
        } else {
            return [
                'icone' => 'https://image.flaticon.com/icons/png/512/616/616450.png',
                'descricao' => 'Signo não encontrado. Por favor, verifique a data de nascimento fornecido.'
            ];
        }
    }


}
