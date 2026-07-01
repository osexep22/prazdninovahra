<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class QuickLaunchSeeder extends Seeder
{
    public function run(): void
    {
        $this->clearOldLocationContent();

        DB::table('game_contents')->updateOrInsert(
            ['key' => 'intro_story'],
            [
                'title' => 'Jak začala Prázdninová hra',
                'body_top' => "Jednoho rána našel malý mravenec uprostřed palouku mapu posetou kapkami rosy. Vypadala obyčejně, ale když na ni dopadl první paprsek slunce, rozsvítila se tajná stezka.",
                'image_path' => '/assets/game/story-ant-friends.png',
                'body_bottom' => "A tak začíná výprava za šiframi, broučky a stavbou vlastního mraveniště. Splň úkoly na palouku, získej suroviny a pomoz kolonii objevit srdce prázdnin.",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $locations = [
            [1, 'ukol-1', 'Roháč', 35, 66, [], 'Než najdeme cestu zpátky, postavíme si provizorní dům. Potřebujeme dřevo, ale místo, kde je ho opravdu hodně, prý hlídá roháč.', 'Slyšíš, že se tu řeže dříví. Třeba budou mít trámky i pro tebe.', 'Tomuhle by roháč neměl rozumět. Ty snad ano. Najdi místo s krásným výhledem na Krucemburk a Benátky a zjisti poslední tři slova.', 'poskozeni-se-tresta'],
            [2, 'ukol-2', 'Červotoč', 61, 25, ['ukol-1'], 'Dřevo už máme, ale musíme ho upravit. Pomoci by mohl červotoč Jirka, jen potřebujeme zjistit, kde bydlí.', 'Z kmene se ozývá tiché ťukání. Někdo tam asi kutá chodbičku.', 'Červotoč poslal zprávu divným písmem. Hledej místo, kde je hodně dřeva, které se opracovává. Pomník nebo socha tě nasměruje a pomůže poslední uvedený rok.', '1942'],
            [3, 'ukol-3', 'Vážka', 45, 29, [], 'I mravenci potřebují v létě pít. U Karla bylo vody dost, tady ale musíme najít dobrý zdroj.', 'V dálce vidíš, jak se nad hladinou něco mihotá. Pojď se podívat, co tam létá.', 'Vážka radí pohádkový potok. Dej pozor u hráze a zjisti, kde se řešily kotlíkové dotace.', 'kadibudka'],
            [4, 'ukol-4', 'Šnek', 55, 50, ['ukol-3'], 'Máme hlad. U potoka se povídá, že šnek má moc krásnou zahrádku.', 'Mezi listy se pomalu leskne cestička. Někdo tu asi táhne svačinu.', 'Zjisti, kde se zahrádka nachází. Na místě tě čeká zrychlená osmisměrka a stopa k lavičce Chlum.', 'lavicka-chlum'],
            [5, 'ukol-5', 'Stonožka', 53, 65, ['ukol-2', 'ukol-4'], 'Stonožka zve mravence na taneční vystoupení. Prý se máme nahlásit jako VIP MRAVENCI.', 'Z trávy se ozývá rytmus mnoha nožiček. Tady se určitě něco nacvičuje.', 'Přišla šifra bez úvodní věty. Čas na odpočinek, moře, řeka a nektar. Opatrně, ať v mraveništi nevznikne okno navíc.', 'vip-mravenci'],
            [6, 'ukol-6', 'Vodměrka', 46, 49, ['ukol-5'], 'Po palouku teče potok a přes hladinu po něm běhá vodoměrka jako po skle.', 'U malé přehrádky se voda ani nehne, ale někdo po ní chodí suchou nohou.', 'Vodoměrka umí najít cestu přes vodu. Sleduj proud, malou přehrádku a zapiš si znak, který hlídá nejklidnější tůňku.', 'tunka'],
            [7, 'ukol-7', 'Beruška', 76, 41, ['ukol-6'], 'Beruška počítá tečky a tvrdí, že každá dobrá mapa potřebuje správný směr.', 'Na listu sedí puntíkatá průvodkyně a tváří se, že ví kudy dál.', 'Najdi místo, odkud jsou vidět tři barvy palouku. Spočítej tečky, které ukazují cestu k další stopě.', 'sedm-tecek'],
            [8, 'ukol-8', 'Včela', 73, 20, ['ukol-5'], 'Včela zná všechny květiny na palouku a ukryla mezi ně sladkou zprávu.', 'Nad největším květem to bzučí. Možná tam někdo schoval sladkou nápovědu.', 'Hledej největší květ u cesty. V jeho okolí najdeš nápovědu k tomu, co mravenci potřebují pro další stavbu.', 'nektar'],
            [9, 'ukol-9', 'Světluška', 67, 43, ['ukol-8'], 'Světluška hlídá cestu, která je vidět až když se palouk začne stmívat.', 'Pod stromem problikává malé světýlko. Ve dne skoro není vidět.', 'Najdi světelný bod u stromu na kraji palouku. Správná odpověď je slovo, které tam svítí nejdéle.', 'lucerna'],
            [10, 'ukol-10', 'Kobylka', 58, 58, ['ukol-9', 'ukol-7'], 'Na okraji palouku se mezi st?bly chystaj? p?ek??ky. Kobylka tvrd?, ?e spr?vn? skok najde jen ten, kdo u? posb?ral dost stop.', 'V tr?v? vid?? p?ipravenou p?ek??kovou dr?hu. N?kdo tam ?ek? na odv??n? skok.', 'Kobylka p?ipravila posledn? sk?kac? ?ifru. Sleduj p?ek??ky a najdi heslo, kter? otev?e dal?? krok mraven?? v?pravy.', 'kobylka'],
        ];

        $locationImages = [
            1 => '/assets/game/stations-v3/task-1-rohac-active-water-transparent-edgefade.png',
            2 => '/assets/game/stations-final/task-2-cervotoc-final-v2-edgefade.png',
            3 => '/assets/game/stations-final/task-3-vazka-final-v2-edgefade.png',
            4 => '/assets/game/stations-final/task-4-snek-final-edgefade.png',
            5 => '/assets/game/stations-final/task-5-stonozka-final-edgefade.png',
            6 => '/assets/game/stations-final/task-6-vodomerka-final-edgefade.png',
            7 => '/assets/game/stations-final/task-7-beruska-final-edgefade.png',
            8 => '/assets/game/stations-final/task-8-vcela-final-edgefade.png',
            9 => '/assets/game/stations-final/task-9-svetluska-final-v2-edgefade.png',
            10 => '/assets/game/stations-final/kobylka-final-v3-edgefade.png',
        ];

        $ids = [];
        $lockedDescriptions = [
            1 => 'U dřeva se něco pohnulo. Sem už máš důvod vyrazit.',
            2 => 'Z kmene se ozývá škrábání, ale zatím nevíš, proč bys tam chodil.',
            3 => 'Nad vodou se něco mihotá. Tohle místo už volá po průzkumu.',
            4 => 'Za listem se leskne slizká stopa, ale nejdřív potřebuješ stopu od vody.',
            5 => 'Tráva se rytmicky vlní, jen ještě neznáš správné kroky.',
            6 => 'U přehrádky se hýbe hladina, ale zatím nemáš důvod lézt k vodě.',
            7 => 'Na listu probleskují tečky, jen ještě nevíš, co počítat.',
            8 => 'Květiny bzučí sladkým tajemstvím, ale cesta k nim se teprve otevře.',
            9 => 'Ve stínu stromu něco problikává, ale den je zatím moc jasný.',
            10 => 'V tr?v? se n?co prudce mihlo, ale je?t? nev??, kam kobylka dosko??.',
        ];
        foreach ($locations as [$order, $slug, $name, $x, $y, $requirements, $story, $tooltip]) {
            DB::table('locations')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $lockedDescriptions[$order],
                    'story' => $story,
                    'is_active' => true,
                    'reward_prestige' => 60 + ($order * 10),
                    'reward_resources' => 15 + ($order * 3),
                    'reward_colony_level' => in_array($order, [2, 5, 8, 10], true) ? 1 : 0,
                    'image_path' => $locationImages[$order],
                    'story_image_path' => '/assets/game/story-ant-friends.png',
                    'tooltip' => $tooltip,
                    'sort_order' => $order,
                    'map_x' => $x,
                    'map_y' => $y,
                    'svg_locked' => '/assets/placeholders/location-locked.svg',
                    'svg_available' => '/assets/placeholders/location-available.svg',
                    'svg_completed' => '/assets/placeholders/location-completed.svg',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $ids[$slug] = DB::table('locations')->where('slug', $slug)->value('id');
        }

        $this->seedBadges($locations);

        DB::table('location_requirements')->whereIn('location_id', array_values($ids))->delete();
        foreach ($locations as [, $slug, , , , $requirements]) {
            foreach ($requirements as $requiredSlug) {
                DB::table('location_requirements')->updateOrInsert([
                    'location_id' => $ids[$slug],
                    'required_location_id' => $ids[$requiredSlug],
                ]);
            }
        }

        $taskIdsByOrder = [];
        DB::table('location_tasks')->whereIn('location_id', array_values($ids))->delete();
        foreach ($locations as [$order, $slug, $name, , , , , , $body, $answer]) {
            $taskId = DB::table('location_tasks')->insertGetId([
                'location_id' => $ids[$slug],
                'type' => 'code',
                'title' => 'Úkol ' . $order . ': ' . $name,
                'body' => $body,
                'answer_hash' => Hash::make($answer),
                'required_for_completion' => true,
                'reward_prestige' => 25 + ($order * 3),
                'reward_resources' => 8 + $order,
                'pdf_path' => '/assets/game/test-task.pdf',
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $taskIdsByOrder[$order] = $taskId;
            DB::table('task_hints')->insert([
                'location_task_id' => $taskId,
                'text' => 'Testovací nápověda pro adminy: odpověď je "' . $answer . '".',
                'cost_resources' => 5,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->seedPlayers($ids, $taskIdsByOrder);
    }

    private function seedPlayers(array $locationIds, array $taskIdsByOrder): void
    {
        $players = [
            ['tester-start', 'Tester Start', 'TSTART01', 1, []],
            ['tester-ukol1', 'Tester po ukolu 1', 'TUKOL001', 1, [1]],
            ['tester-stred', 'Tester uprostred hry', 'TSTRED01', 2, [1, 2, 3, 4, 5]],
            ['tester-finale', 'Tester pred finale', 'TFINAL01', 3, [1, 2, 3, 4, 5, 6, 7, 8, 9]],
        ];

        foreach ($players as [$username, $name, $friendCode, $level, $completed]) {
            DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'display_name' => $name,
                    'name' => $name,
                    'email' => null,
                    'role' => 'player',
                    'status' => 'active',
                    'password' => Hash::make('heslo123'),
                    'admin_contact_password' => Hash::make('admin-heslo'),
                    'admin_contact_code_hash' => Hash::make('admin-kod'),
                    'registration_source' => 'test',
                    'friend_code' => $friendCode,
                    'colony_level' => $level,
                    'resources' => 100 + count($completed) * 20,
                    'prestige' => count($completed) * 90,
                    'intro_seen_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $userId = DB::table('users')->where('username', $username)->value('id');
            foreach ($completed as $order) {
                $locationId = $locationIds['ukol-' . $order] ?? null;
                $taskId = $taskIdsByOrder[$order] ?? null;
                if (! $locationId || ! $taskId) {
                    continue;
                }
                DB::table('user_location_progress')->updateOrInsert(
                    ['user_id' => $userId, 'location_id' => $locationId],
                    ['status' => 'completed', 'completed_at' => now()]
                );
                DB::table('user_task_progress')->updateOrInsert(
                    ['user_id' => $userId, 'location_task_id' => $taskId],
                    ['status' => 'completed', 'submitted_answer' => 'ukol-' . $order, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function clearOldLocationContent(): void
    {
        DB::table('user_hint_purchases')->delete();
        DB::table('user_task_progress')->delete();
        DB::table('user_location_progress')->delete();
        DB::table('task_hints')->delete();
        DB::table('location_tasks')->delete();
        DB::table('location_requirements')->delete();
        DB::table('locations')->delete();
    }

    private function seedBadges(array $locations): void
    {
        foreach ($locations as [$order, $slug, $name]) {
            DB::table('badges')->updateOrInsert(
                ['slug' => 'lokace-' . $slug],
                [
                    'name' => 'Dokončeno: ' . $name,
                    'description' => 'Odznáček za dokončení úkolu ' . $order . '.',
                    'icon_path' => '/assets/badges/location-' . $order . '.png',
                    'prestige_bonus' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            DB::table('badges')->updateOrInsert(
                ['slug' => 'top-10-' . $slug],
                [
                    'name' => 'První desítka: ' . $name,
                    'description' => 'Odznáček pro prvních 10 hráčů, kteří dokončí úkol ' . $order . '.',
                    'icon_path' => '/assets/badges/top10-' . $order . '.png',
                    'prestige_bonus' => 25,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        foreach ([100, 250, 500, 1000] as $threshold) {
            DB::table('badges')->updateOrInsert(
                ['slug' => 'prestiz-' . $threshold],
                [
                    'name' => 'Prestiž ' . $threshold,
                    'description' => 'Odznáček za dosažení ' . $threshold . ' prestiže.',
                    'icon_path' => '/assets/badges/prestige-' . $threshold . '.png',
                    'prestige_bonus' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
