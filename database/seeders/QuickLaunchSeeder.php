<?php

namespace Database\Seeders;

use App\Support\AnswerNormalizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
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
                'title' => 'Ahoj, já jsem Rosťa',
                'body_top' => "Ahoj!\n\nJsem Rosťa Mravenec domácí! Tedy... býval jsem. Včera se mi totiž stala taková divná věc. Vydal jsem se s kámoši na lov jídla. Děláme to tak pravidelně každý den v 8 ráno. Jenže tentokrát bylo všechno jídlo v takových divných průhledných miskách. Měli jsme ale hlad, takže jsme tam vlezli. Ale pak přišel Karel. Tak jsme se schovali za jídlo. Jenže on nás zavřel, vzal a hodil nás někam do tmy. A pak s námi házel. Hodně a dlouho.",
                'image_path' => '/assets/game/story/intro-throw.png',
                'body_middle' => "Po nějaké době se objevilo světlo na konci tunelu. A potom křik malé Marušky, když si nás všimla, že jsme v jejich oblíbených sušenkách. No a poslední, co si pamatuju, je dlouhý let, tvrdý náraz, a pak tma. A teď je ráno a já tu stojím se svými kámoši na paloučku.",
                'image_path_2' => '/assets/game/story/intro-meadow.png',
                'body_bottom' => "Nevíme, co máme dělat. Chceme jediné - dostat se zpátky ke Karlově rodině. Pomůžeš nám prosím?",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $locations = [
            [
                'order' => 1,
                'slug' => 'ukol-1',
                'name' => 'Roháč',
                'x' => 35,
                'y' => 66,
                'requirements' => [],
                'story' => "Rosťa se rozhlédl po mokré trávě a pobouřeně mávl tykadly. Po tom všem, co mravenci pro lidskou rodinu udělali, je prostě odnesli doprostřed divočiny. Prý kousali a lezli do sušenek. Jenže vzdát se? To ani náhodou.\n\nNež výprava najde cestu zpátky ke Karlovi, potřebuje provizorní přístřeší. Na holé zemi se spát nedá a noc v lese umí být chladná. Rosťa si všiml místa, kde leží hromada nádherných klád a větviček. Místní brouci šeptají, že dřevo patří starému roháčovi, který ho sbíral celé jaro. Rosťa si ale myslí, že mravenci ho potřebují víc.\n\nAby roháč nic netušil, zašifroval polohu. Rozlušti zprávu, najdi dřevo a přines poslední tři slova z místa, odkud je krásný výhled na Krucemburk a Benátky.",
                'completed' => "Dřevo je na místě. Roháč sice zmateně pobíhal kolem své zmenšené hromady a výhružně cvakal kusadly, ale mravenčí výprava má materiál na první přístřeší. Všechno se zdá jako skvělý začátek. Jenže malý neklid v tykadlech napovídá, že brát si cizí věci bez ptaní možná nebyl úplně nejlepší nápad.",
                'tooltip' => 'Jdu hledat dříví. Támhle slyším, že někdo řeže.',
                'tooltip_completed' => 'Dřevo je přinesené, ale roháčovi bude potřeba se jednou omluvit.',
                'task' => "Tomuhle by roháč neměl rozumět. Ty snad ano.\n\nOpravdu spoustu dřeva najdeš na místě, kde je krásný výhled na Krucemburk a Benátky. Pokus se nevzbudit moc pozornosti a zjisti poslední tři slova. Snad to nebude nic vážného.",
                'answer' => 'POŠKOZENÍ SE TRESTÁ',
            ],
            [
                'order' => 2,
                'slug' => 'ukol-2',
                'name' => 'Červotoč',
                'x' => 61,
                'y' => 25,
                'requirements' => ['ukol-1'],
                'story' => "Dřevo je sice získané, ale v surovém stavu z něj mraveniště nepostaví ani ten nejsebevědomější mravenec. Klády jsou tlusté, kůra tvrdá a kusadla výpravy nejsou dělaná na pořádnou dřevařinu.\n\nNěkde poblíž prý bydlí červotoč Jirka. Umí se dřevem zázraky a dokáže z něj připravit hladké stavební dílky. Poslal ale zprávu podivným písmem a Rosťa jí nerozumí. Rozlušti vzkaz, najdi červotoče a zjisti údaj, který ukazuje na správné místo.",
                'completed' => "Červotoč Jirka se pustil do práce s takovou chutí, že větve provrtal a naporcoval na dokonale použitelné dílky. Materiál na první mraveniště je připravený. Výprava má konečně šanci postavit bezpečné zázemí.",
                'tooltip' => 'Z kmene se ozývá tiché šukání. Někdo tam kutá chodbičku.',
                'tooltip_completed' => 'Červotoč dřevo upravil. První stavba může začít.',
                'task' => "Červotoč napsal, kde bydlí, ale jeho písmo je zvláštní.\n\nBývá na místě, kde je hodně dřeva, které se opracovává. Název pomník nebo socha tě nasměruje správným směrem. Pomůže ti poslední uvedený rok.",
                'answer' => '1942',
            ],
            [
                'order' => 3,
                'slug' => 'ukol-3',
                'name' => 'Vážka',
                'x' => 45,
                'y' => 29,
                'requirements' => [],
                'story' => "Slunce začalo pálit a mravenci rychle zjistili, že bez vody dlouho pracovat nevydrží. U Karla doma stačilo najít kapku ve dřezu, ale na palouku je všechno větší a neznámé.\n\nNad kapradím se objevila vážka. Tvrdí, že zná nejlepší vodní zdroj v okolí, ale zadarmo polohu neprozradí. Zanechala hádanku o pohádkovém potoce, hrázi a místě, kde se řešily kotlíkové dotace. Rozlušti její zprávu a najdi vodu.",
                'completed' => "Výprava se napila a doplnila zásoby vody. Jenže při shonu kolem hráze se voda rozběhla víc, než měla. Mravenci si zatím říkají, že hlavní je mít zásoby, ale palouk si takovou škodu dlouho pamatovat bude.",
                'tooltip' => 'Nad hladinou se něco mihotá. Pojď zjistit, co tam létá.',
                'tooltip_completed' => 'Voda je nalezená, ale hráz bude potřebovat opravu.',
                'task' => "Vážka má nejradši pohádkový potok. Jen se musí dávat pozor při procházení kolem hráze. Naposledy se tam řešily kotlíkové dotace, jen už neví kde.",
                'answer' => 'KADIBUDKA',
            ],
            [
                'order' => 4,
                'slug' => 'ukol-4',
                'name' => 'Šnek',
                'x' => 55,
                'y' => 50,
                'requirements' => ['ukol-3'],
                'story' => "Po práci a hledání vody začalo mravencům kručet v břiše. V lese ale nejsou sušenky ani drobky pod stolem. Od místních larev se dozvěděli, že nedaleko má starý šnek krásnou zahrádku plnou šťavnatých listů.\n\nŠnek si svou zahradu schovává před nezvanými hosty. Výprava musí zjistit, kde leží, a na místě spočítat prkna, na kterých si šnek odpočívá.",
                'completed' => "Mravenci se najedli dosyta, ale šnekova zahrádka zůstala poničená. Šnek smutně prohlásil, že takhle mu ji nezdevastovali ani slimáci. Tentokrát to výprava ještě přejde smíchem, ale první stín viny už se pomalu plíží za nimi.",
                'tooltip' => 'Mezi listy se leskne pomalá cestička. Někdo tam schovává svačinu.',
                'tooltip_completed' => 'Šnekova zahrádka je poničená. Tohle bude chtít napravit.',
                'task' => "Zjisti, kde se šnekova zahrádka nachází. Na místě spočítej počet prken, na kterých si šnek může odpočinout.",
                'answer' => '8',
            ],
            [
                'order' => 5,
                'slug' => 'ukol-5',
                'name' => 'Stonožka',
                'x' => 53,
                'y' => 65,
                'requirements' => ['ukol-2', 'ukol-4'],
                'story' => "Přístřeší stojí, voda je v zásobě a břicha jsou plná. Na palouk dorazila pozvánka od stonožky: pořádá velké taneční vystoupení a zve výpravu jako VIP mravence.\n\nRosťa rozhodl, že takovou událost si nesmí nechat ujít. Jenže místo konání je ukryté v pozvánce. Rozlušti text a zjisti, kam se vydat za lesní slavností.",
                'completed' => "Taneční večer se změnil v pohromu. Mravenci se hnali dopředu, skákali do rytmu a úplně přehlédli, že křehký parket pod nimi praská. Stonožka zůstala bez pódia a poprvé se výprava opravdu zastyděla. Možná je čas přestat jen brát a začít napravovat.",
                'tooltip' => 'Z trávy se ozývá rytmus mnoha nožiček. Tady se něco nacvičuje.',
                'tooltip_completed' => 'Parket je rozbitý. Stonožka bude potřebovat pomoc.',
                'task' => "Myslím si, že přichází čas na odpočinek. Máme za sebou spoustu práce. Uf. Na takovou příležitost by bylo nejlepší jet k moři. Nebo by stačila řeka? A tak si dáme nektar. Jen opatrně, ať nemáme v mraveništi okno navíc!",
                'answer' => 'VIP MRAVENCI',
            ],
            [
                'order' => 6,
                'slug' => 'ukol-6',
                'name' => 'Vodoměrka',
                'x' => 46,
                'y' => 49,
                'requirements' => ['ukol-5'],
                'story' => "U trosek parketu výpravu doběhla uplakaná vodoměrka. Kvůli protržené hrázi přišla o klidnou hladinu, na které měřila vodu. Mravenci se konečně podívali jeden na druhého a pochopili, že škoda nezmizí jen proto, že odešli pryč.\n\nVodoměrka jim poradila, kde se inspirovat opravou hráze. Je potřeba dojít k Malému Černému, najít strom se zelenou cedulkou a přinést důležitý údaj.",
                'completed' => "Hráz je opravená. Mravenci nosili bahno, proplétali větvičky a stavěli přesně podle plánu. Vodoměrka je šťastná a voda zase drží tam, kde má. A výprava ví, že napravovat chyby dává mnohem lepší pocit než před nimi utíkat.",
                'tooltip' => 'U malé přehrádky se voda skoro nehýbe. Někdo po ní chodí suchou nohou.',
                'tooltip_completed' => 'Hráz drží pevně. Vodoměrka má znovu klidnou hladinu.',
                'task' => "Abychom opravili hráz, musíme se podívat, jak se správně staví. Vodoměrka poradila hráz u Malého Černého. Kousek od rybníka hned u cesty je strom se zelenou cedulkou. Přines údaj z cedulky.",
                'answer' => '217',
            ],
            [
                'order' => 7,
                'slug' => 'ukol-7',
                'name' => 'Beruška',
                'x' => 76,
                'y' => 41,
                'requirements' => ['ukol-6'],
                'story' => "Největší dluh zůstal u stonožky. Její parket je rozbitý a vystoupení zničené. Naštěstí se objevila beruška, vyhlášená lesní designérka. Má plán na nový taneční parket, jen potřebuje najít speciální místo s materiálem, který neklouže.\n\nSouřadnice a nápovědy vedou k přehledu přírodních rezervací. Zjisti, která je prostřední.",
                'completed' => "Nový parket se leskne, voní dřevem a zní přesně tak, jak má. Stonožka udělala pár rychlých kroků a celý palouk zatleskal. Mravenci poprvé pocítili, že být součástí lesa může být lepší než stát proti němu.",
                'tooltip' => 'Na listu sedí puntíkatá návrhářka a ukazuje cestu k novému parketu.',
                'tooltip_completed' => 'Beruška pomohla postavit parket, na kterém se dá znovu tančit.',
                'task' => "Na místě 49._ _ _ _ _ 0 _N, 15._ _ _ _ _ _ _E je souhrn přírodních rezervací, kde by se dal najít materiál na nový parket. Jaká je prostřední z nich?",
                'answer' => 'DRÁTENICKÁ SKÁLA',
            ],
            [
                'order' => 8,
                'slug' => 'ukol-8',
                'name' => 'Včela',
                'x' => 73,
                'y' => 20,
                'requirements' => ['ukol-5'],
                'story' => "Šnekova poničená zahrádka mravence tížila čím dál víc. Sami zahradníci nejsou, ale potkali pilnou včelku, která zná nejlepší semínka a ví, jak květiny probudit k životu.\n\nVčelka poradí s obnovou zahrádky, ale potřebuje pomoci najít správnou květinu. Zjisti heslo, které otevře cestu k novému začátku pro šneka.",
                'completed' => "Zahrádka znovu ožila. Včelka obletěla budoucí květy, mravenci uklidili záhony a šnek měl v očích slzy dojetí. Výprava pochopila, že napravit škodu může být krásnější než jakákoli hostina.",
                'tooltip' => 'Nad květy bzučí pilná pomocnice. Možná ví, jak vrátit zahrádce život.',
                'tooltip_completed' => 'Šnekova zahrádka zase roste a včelka hlídá první květy.',
                'task' => "Včelka ukryla jméno květiny, která pomůže obnovit šnekovu zahrádku.",
                'answer' => 'MACEŠKA',
            ],
            [
                'order' => 9,
                'slug' => 'ukol-9',
                'name' => 'Světluška',
                'x' => 67,
                'y' => 43,
                'requirements' => ['ukol-8'],
                'story' => "Když byly velké škody napravené, přiletěla světluška. Už od začátku hledala cestu ke Karlovi a teď přinesla seznam pěti možných míst. Mravenci by dřív jásali, že se blíží návrat domů. Jenže po všem, co na palouku prožili, si už nejsou jistí, kam vlastně patří.\n\nVyřeš světluščinu logickou hádanku a zjisti číslo popisné Karlova domu.",
                'completed' => "Cesta ke Karlovi je jasnější. Jenže místo radosti se objevila otázka: musí se výprava opravdu vracet? Palouk už není cizí divočina. Je to místo, kde mají přátele a kde se naučili být lepší.",
                'tooltip' => 'Pod stromem problikává malé světýlko. Má zprávu o cestě domů.',
                'tooltip_completed' => 'Světluška ukázala cestu, ale mravenci váhají, kam patří.',
                'task' => "Světluška přinesla seznam jmen, barev domů, nápojů, čísel popisných a míst. Použij její indicie a zjisti číslo popisné Karlova domu.\n\nKarel má číslo popisné 496. Ostatní indicie ověřují, zda držíš správnou cestu.",
                'answer' => '496',
            ],
            [
                'order' => 10,
                'slug' => 'ukol-10',
                'name' => 'Kobylka',
                'x' => 58,
                'y' => 58,
                'requirements' => ['ukol-9', 'ukol-7'],
                'story' => "U opravené hráze čekala kobylka, roháčova pomocnice. Připomněla mravencům, kolik práce dalo nasbírat dřevo, které si na začátku bez dovolení vzali. Roháč je ochotný odpustit, pokud mu pomohou zkontrolovat vzácný strom, na který už sám nevyleze.\n\nVydej se podle šifry na správné místo a zjisti, o jaký strom jde.",
                'completed' => "Mravenci vylezli vysoko do větví, zkontrolovali kůru i listy a přinesli roháčovi dobrou zprávu. Omluva byla přijata. Výprava už není parta zlodějů ze sušenkové krabice, ale společenství, které umí pomáhat. Palouk se stal domovem.",
                'tooltip' => 'V trávě je připravená překážková dráha. Kobylka čeká na odvážný skok.',
                'tooltip_completed' => 'Kobylka donesla omluvu roháčovi. Na palouku je zase klid.',
                'task' => "Roháč požádal, zda byste se nepodívali na stav jednoho stromu. Poznáš, co je to za strom? Je vlevo na poslední odbočce na místo, které ukrývá tajenka: Udělej si výlet na Babylón.",
                'answer' => 'HRUŠEŇ',
            ],
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

        $storyImages = [
            1 => '/assets/game/story/task-1-rohac.png',
            2 => '/assets/game/story/task-2-cervotoc.png',
            3 => '/assets/game/story/task-3-vazka.png',
            4 => '/assets/game/story/task-4-snek.png',
            5 => '/assets/game/story/task-5-stonozka.png',
        ];

        $taskPdfs = [
            1 => '/assets/game/tasks/task-1-rohac.pdf',
            2 => '/assets/game/tasks/task-2-cervotoc.pdf',
            3 => '/assets/game/tasks/task-3-vazka.pdf',
            4 => '/assets/game/tasks/task-4-snek.pdf',
            5 => '/assets/game/tasks/task-5-stonozka.pdf',
        ];

        $ids = [];
        foreach ($locations as $location) {
            DB::table('locations')->updateOrInsert(
                ['slug' => $location['slug']],
                [
                    'name' => $location['name'],
                    'description' => $location['tooltip'],
                    'story' => $location['story'],
                    'story_completed' => $location['completed'],
                    'is_active' => true,
                    'reward_prestige' => 60 + ($location['order'] * 10),
                    'reward_resources' => 15 + ($location['order'] * 3),
                    'reward_colony_level' => 1,
                    'image_path' => $locationImages[$location['order']],
                    'story_image_path' => $storyImages[$location['order']] ?? null,
                    'tooltip' => $location['tooltip'],
                    'tooltip_completed' => $location['tooltip_completed'],
                    'sort_order' => $location['order'],
                    'map_x' => $location['x'],
                    'map_y' => $location['y'],
                    'svg_locked' => '/assets/placeholders/location-locked.svg',
                    'svg_available' => '/assets/placeholders/location-available.svg',
                    'svg_completed' => '/assets/placeholders/location-completed.svg',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
            $ids[$location['slug']] = DB::table('locations')->where('slug', $location['slug'])->value('id');
        }

        $this->seedBadges($locations);

        DB::table('location_requirements')->whereIn('location_id', array_values($ids))->delete();
        foreach ($locations as $location) {
            foreach ($location['requirements'] as $requiredSlug) {
                DB::table('location_requirements')->updateOrInsert([
                    'location_id' => $ids[$location['slug']],
                    'required_location_id' => $ids[$requiredSlug],
                ]);
            }
        }

        $taskIdsByOrder = [];
        DB::table('location_tasks')->whereIn('location_id', array_values($ids))->delete();
        foreach ($locations as $location) {
            $taskId = DB::table('location_tasks')->insertGetId([
                'location_id' => $ids[$location['slug']],
                'type' => 'code',
                'title' => 'Úkol ' . $location['order'] . ': ' . $location['name'],
                'body' => $location['task'],
                'answer_hash' => Hash::make(AnswerNormalizer::normalize($location['answer'])),
                'required_for_completion' => true,
                'reward_prestige' => 25 + ($location['order'] * 3),
                'reward_resources' => 8 + $location['order'],
                'pdf_path' => $taskPdfs[$location['order']] ?? null,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $taskIdsByOrder[$location['order']] = $taskId;
            DB::table('task_hints')->insert([
                'location_task_id' => $taskId,
                'text' => 'Když se zasekneš, zkus odpověď zapsat jednoduše a bez zdobení. Správná stopa míří k: ' . mb_strtolower($location['answer'], 'UTF-8') . '.',
                'cost_resources' => 20,
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
            ['vyprava-zacatek', 'Rosťa na začátku', 'R1S2T3', 1, []],
            ['vyprava-drevo', 'Dřevo přineseno', 'D2R3E4', 2, [1]],
            ['vyprava-obrat', 'Výprava uprostřed', 'S3T4R5', 6, [1, 2, 3, 4, 5]],
            ['vyprava-finale', 'Těsně před domovem', 'F4N5L6', 10, [1, 2, 3, 4, 5, 6, 7, 8, 9]],
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
                    'admin_contact_code_encrypted' => Crypt::encryptString('admin-kod'),
                    'registration_source' => 'vyprava',
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
                    ['status' => 'completed', 'submitted_answer' => 'splněno', 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]
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
        foreach ($locations as $location) {
            DB::table('badges')->updateOrInsert(
                ['slug' => 'lokace-' . $location['slug']],
                [
                    'name' => 'Dokončeno: ' . $location['name'],
                    'description' => 'Odznáček za dokončení stanoviště ' . $location['name'] . '.',
                    'icon_path' => '/assets/badges/location-' . $location['order'] . '.png',
                    'prestige_bonus' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            DB::table('badges')->updateOrInsert(
                ['slug' => 'top-10-' . $location['slug']],
                [
                    'name' => 'První desítka: ' . $location['name'],
                    'description' => 'Odznáček pro prvních 10 hráčů, kteří dokončí stanoviště ' . $location['name'] . '.',
                    'icon_path' => '/assets/badges/top10-' . $location['order'] . '.png',
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
                    'description' => 'Odznáček za dosažení ' . $threshold . ' bodů prestiže.',
                    'icon_path' => '/assets/badges/prestige-' . $threshold . '.png',
                    'prestige_bonus' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
