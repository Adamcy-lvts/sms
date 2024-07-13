<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class LgaTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (DB::table('lgas')->count() == 0) {
            //Abia
            DB::table('lgas')->insert([

                ['name' => "Aba North",         'state_id' => 1],
                ['name' => "Aba South",         'state_id' => 1],
                ['name' => "Arochukwu",         'state_id' => 1],
                ['name' => "Bende",             'state_id' => 1],
                ['name' => "Ikwuano",           'state_id' => 1],
                ['name' => "Isiala-Ngwa North", 'state_id' => 1],
                ['name' => "Isiala-Ngwa South", 'state_id' => 1],
                ['name' => "Isuikwato",         'state_id' => 1],
                ['name' => "Obi Nwa",           'state_id' => 1],
                ['name' => "Ohafia",            'state_id' => 1],
                ['name' => "Osisioma",          'state_id' => 1],
                ['name' => "Ngwa",              'state_id' => 1],
                ['name' => "Ugwunagbo",         'state_id' => 1],
                ['name' => "Ukwa East",         'state_id' => 1],
                ['name' => "Ukwa West",         'state_id' => 1],
                ['name' => "Umuahia North",     'state_id' => 1],
                ['name' => "Umuahia South",     'state_id' => 1],
                ['name' => "Umu-Neochi",        'state_id' => 1],



            ]);

            //Adamawa
            DB::table('lgas')->insert([

                ['name' =>   "Demsa",       'state_id' => 2],
                ['name' =>   "Fufore",      'state_id' => 2],
                ['name' =>   "Ganaye",      'state_id' => 2],
                ['name' =>   "Gireri",      'state_id' => 2],
                ['name' =>   "Gombi",       'state_id' => 2],
                ['name' =>   "Guyuk",       'state_id' => 2],
                ['name' =>   "Hong",        'state_id' => 2],
                ['name' =>   "Jada",        'state_id' => 2],
                ['name' =>   "Lamurde",     'state_id' => 2],
                ['name' =>   "Madagali",    'state_id' => 2],
                ['name' =>   "Maiha",       'state_id' => 2],
                ['name' =>   "Mayo-Belwa",  'state_id' => 2],
                ['name' =>   "Michika",     'state_id' => 2],
                ['name' =>   "Mubi North",  'state_id' => 2],
                ['name' =>   "Mubi South",  'state_id' => 2],
                ['name' =>   "Numan",       'state_id' => 2],
                ['name' =>   "Shelleng",    'state_id' => 2],
                ['name' =>   "Song",        'state_id' => 2],
                ['name' =>   "Toungo",      'state_id' => 2],
                ['name' =>   "Yola North",  'state_id' => 2],
                ['name' =>   "Yola South",  'state_id' => 2]



            ]);

            //Anamabra
            DB::table('lgas')->insert([

                ['name' => "Aguata",       'state_id' => 3],
                ['name' => "Anambra East", 'state_id' => 3],
                ['name' => "Anambra West", 'state_id' => 3],
                ['name' => "Anaocha",      'state_id' => 3],
                ['name' => "Awka North",   'state_id' => 3],
                ['name' => "Awka South",   'state_id' => 3],
                ['name' => "Ayamelum",     'state_id' => 3],
                ['name' => "Dunukofia",    'state_id' => 3],
                ['name' => "Ekwusigo",     'state_id' => 3],
                ['name' => "Idemili North", 'state_id' => 3],
                ['name' => "Idemili south", 'state_id' => 3],
                ['name' => "Ihiala",       'state_id' => 3],
                ['name' => "Njikoka",      'state_id' => 3],
                ['name' => "Nnewi North",  'state_id' => 3],
                ['name' => "Nnewi South",  'state_id' => 3],
                ['name' => "Ogbaru",       'state_id' => 3],
                ['name' => "Onitsha North", 'state_id' => 3],
                ['name' => "Onitsha South", 'state_id' => 3],
                ['name' => "Orumba North", 'state_id' => 3],
                ['name' => "Orumba South", 'state_id' => 3],
                ['name' => "Oyi",          'state_id' => 3]


            ]);


            //Akwa Ibom  

            DB::table('lgas')->insert([

                ['name' => "Abak",                'state_id' => 4],
                ['name' => "Eastern Obolo",       'state_id' => 4],
                ['name' => "Eket",                'state_id' => 4],
                ['name' => "Esit Eket",           'state_id' => 4],
                ['name' => "Essien Udim",         'state_id' => 4],
                ['name' => "Etim Ekpo",           'state_id' => 4],
                ['name' => "Etinan",              'state_id' => 4],
                ['name' => "Ibeno",               'state_id' => 4],
                ['name' => "Ibesikpo Asutan",     'state_id' => 4],
                ['name' => "Ibiono Ibom",         'state_id' => 4],
                ['name' => "Ika",                 'state_id' => 4],
                ['name' => "Ikono",               'state_id' => 4],
                ['name' => "Ikot Abasi",          'state_id' => 4],
                ['name' => "Ikot Ekpene",         'state_id' => 4],
                ['name' => "Ini",                 'state_id' => 4],
                ['name' => "Itu",                 'state_id' => 4],
                ['name' => "Mbo",                 'state_id' => 4],
                ['name' => "Mkpat Enin",          'state_id' => 4],
                ['name' => "Nsit Atai",           'state_id' => 4],
                ['name' => "Nsit Ibom",           'state_id' => 4],
                ['name' => "Nsit Ubium",          'state_id' => 4],
                ['name' => "Obot Akara",          'state_id' => 4],
                ['name' => "Okobo",               'state_id' => 4],
                ['name' => "Onna",                'state_id' => 4],
                ['name' => "Oron",                'state_id' => 4],
                ['name' => "Oruk Anam",           'state_id' => 4],
                ['name' => "Udung Uko",           'state_id' => 4],
                ['name' => "Ukanafun",            'state_id' => 4],
                ['name' => "Uruan",               'state_id' => 4],
                ['name' => "Urue-Offong/Oruko ",  'state_id' => 4],
                ['name' => "Uyo",                 'state_id' => 4]

            ]);

            //Bauchi


            DB::table('lgas')->insert([

                ['name' => "Alkaleri",     'state_id' => 5],
                ['name' => "Bauchi",       'state_id' => 5],
                ['name' => "Bogoro",       'state_id' => 5],
                ['name' => "Damban",       'state_id' => 5],
                ['name' => "Darazo",       'state_id' => 5],
                ['name' => "Dass",         'state_id' => 5],
                ['name' => "Ganjuwa",      'state_id' => 5],
                ['name' => "Giade",        'state_id' => 5],
                ['name' => "Itas/Gadau",   'state_id' => 5],
                ['name' => "Jama'are",     'state_id' => 5],
                ['name' => "Katagum",      'state_id' => 5],
                ['name' => "Kirfi",        'state_id' => 5],
                ['name' => "Misau",        'state_id' => 5],
                ['name' => "Ningi",        'state_id' => 5],
                ['name' => "Shira",        'state_id' => 5],
                ['name' => "Tafawa-Balewa", 'state_id' => 5],
                ['name' => "Toro",         'state_id' => 5],
                ['name' => "Warji",        'state_id' => 5],
                ['name' => "Zaki",         'state_id' => 5]

            ]);

            //"Bayelsa"
            DB::table('lgas')->insert([

                ['name' =>  "Brass",            'state_id' => 6],
                ['name' =>  "Ekeremor",         'state_id' => 6],
                ['name' =>  "Kolokuma/Opokuma", 'state_id' => 6],
                ['name' =>  "Nembe",            'state_id' => 6],
                ['name' =>  "Ogbia",            'state_id' => 6],
                ['name' =>  "Sagbama",          'state_id' => 6],
                ['name' =>  "Southern Jaw",     'state_id' => 6],
                ['name' =>  "Yenegoa",          'state_id' => 6]

            ]);

            //"Benue"
            DB::table('lgas')->insert([

                ['name' =>  "Ado",          'state_id' => 7],
                ['name' =>  "Agatu",        'state_id' => 7],
                ['name' =>  "Apa",          'state_id' => 7],
                ['name' =>  "Buruku",       'state_id' => 7],
                ['name' =>  "Gboko",        'state_id' => 7],
                ['name' =>  "Guma",         'state_id' => 7],
                ['name' =>  "Gwer East",    'state_id' => 7],
                ['name' =>  "Gwer West",    'state_id' => 7],
                ['name' =>  "Katsina-Ala",  'state_id' => 7],
                ['name' =>  "Konshisha",    'state_id' => 7],
                ['name' =>  "Kwande",       'state_id' => 7],
                ['name' =>  "Logo",         'state_id' => 7],
                ['name' =>  "Makurdi",      'state_id' => 7],
                ['name' =>  "Obi",          'state_id' => 7],
                ['name' =>  "Ogbadibo",     'state_id' => 7],
                ['name' =>  "Oju",          'state_id' => 7],
                ['name' =>  "Okpokwu",      'state_id' => 7],
                ['name' =>  "Ohimini",      'state_id' => 7],
                ['name' =>  "Oturkpo",      'state_id' => 7],
                ['name' =>  "Tarka",        'state_id' => 7],
                ['name' =>  "Ukum",         'state_id' => 7],
                ['name' =>  "Ushongo",      'state_id' => 7],
                ['name' =>  "Vandeikya",    'state_id' => 7]

            ]);

            //"Borno"
            DB::table('lgas')->insert([

                ['name' =>   "Abadam",       'state_id' => 8],
                ['name' =>   "Askira/Uba",   'state_id' => 8],
                ['name' =>   "Bama",         'state_id' => 8],
                ['name' =>   "Bayo",         'state_id' => 8],
                ['name' =>   "Biu",          'state_id' => 8],
                ['name' =>   "Chibok",       'state_id' => 8],
                ['name' =>   "Damboa",       'state_id' => 8],
                ['name' =>   "Dikwa",        'state_id' => 8],
                ['name' =>   "Gubio",        'state_id' => 8],
                ['name' =>   "Guzamala",     'state_id' => 8],
                ['name' =>   "Gwoza",        'state_id' => 8],
                ['name' =>   "Hawul",        'state_id' => 8],
                ['name' =>   "Jere",         'state_id' => 8],
                ['name' =>   "Kaga",         'state_id' => 8],
                ['name' =>   "Kala/Balge",   'state_id' => 8],
                ['name' =>   "Konduga",      'state_id' => 8],
                ['name' =>   "Kukawa",       'state_id' => 8],
                ['name' =>   "Kwaya Kusar",  'state_id' => 8],
                ['name' =>   "Mafa",         'state_id' => 8],
                ['name' =>   "Magumeri",     'state_id' => 8],
                ['name' =>   "Maiduguri",    'state_id' => 8],
                ['name' =>   "Marte",        'state_id' => 8],
                ['name' =>   "Mobbar",       'state_id' => 8],
                ['name' =>   "Monguno",      'state_id' => 8],
                ['name' =>   "Ngala",        'state_id' => 8],
                ['name' =>   "Nganzai",      'state_id' => 8],
                ['name' =>   "Shani",        'state_id' => 8]

            ]);

            //"Cross River"
            DB::table('lgas')->insert([

                ['name' =>  "Akpabuyo",                 'state_id' => 9],
                ['name' =>  "Odukpani",                 'state_id' => 9],
                ['name' =>  "Akamkpa",                  'state_id' => 9],
                ['name' =>  "Biase",                    'state_id' => 9],
                ['name' =>  "Abi",                      'state_id' => 9],
                ['name' =>  "Ikom",                     'state_id' => 9],
                ['name' =>  "Yarkur",                   'state_id' => 9],
                ['name' =>  "Odubra",                   'state_id' => 9],
                ['name' =>  "Boki",                     'state_id' => 9],
                ['name' =>  "Ogoja",                    'state_id' => 9],
                ['name' =>  "Yala",                     'state_id' => 9],
                ['name' =>  "Obanliku",                 'state_id' => 9],
                ['name' =>  "Obudu",                    'state_id' => 9],
                ['name' =>  "Calabar South",            'state_id' => 9],
                ['name' =>  "Etung",                    'state_id' => 9],
                ['name' =>  "Bekwara",                  'state_id' => 9],
                ['name' =>  "Bakassi",                  'state_id' => 9],
                ['name' =>  "Calabar Municipality",     'state_id' => 9]

            ]);

            //"Delta"
            DB::table('lgas')->insert([

                ['name' => "Oshimili",            'state_id' => 10],
                ['name' => "Aniocha",             'state_id' => 10],
                ['name' => "Aniocha South",       'state_id' => 10],
                ['name' => "Ika South",           'state_id' => 10],
                ['name' => "Ika North-East",      'state_id' => 10],
                ['name' => "Ndokwa West",         'state_id' => 10],
                ['name' => "Ndokwa East",         'state_id' => 10],
                ['name' => "Isoko south",         'state_id' => 10],
                ['name' => "Isoko North",         'state_id' => 10],
                ['name' => "Bomadi",              'state_id' => 10],
                ['name' => "Burutu",              'state_id' => 10],
                ['name' => "Ughelli South",       'state_id' => 10],
                ['name' => "Ughelli North",       'state_id' => 10],
                ['name' => "Ethiope West",        'state_id' => 10],
                ['name' => "Ethiope East",        'state_id' => 10],
                ['name' => "Sapele",              'state_id' => 10],
                ['name' => "Okpe",                'state_id' => 10],
                ['name' => "Warri North",         'state_id' => 10],
                ['name' => "Warri South",         'state_id' => 10],
                ['name' => "Uvwie",               'state_id' => 10],
                ['name' => "Udu",                 'state_id' => 10],
                ['name' => "Warri Central",       'state_id' => 10],
                ['name' => "Ukwani",              'state_id' => 10],
                ['name' => "Oshimili North",      'state_id' => 10],
                ['name' => "Patani",              'state_id' => 10]

            ]);

            //"Ebonyi
            DB::table('lgas')->insert([

                ['name' => "Afikpo South",     'state_id' => 11],
                ['name' => "Afikpo North",     'state_id' => 11],
                ['name' => "Onicha",           'state_id' => 11],
                ['name' => "Ohaozara",         'state_id' => 11],
                ['name' => "Abakaliki",        'state_id' => 11],
                ['name' => "Ishielu",          'state_id' => 11],
                ['name' => "lkwo",             'state_id' => 11],
                ['name' => "Ezza",             'state_id' => 11],
                ['name' => "Ezza South",       'state_id' => 11],
                ['name' => "Ohaukwu",          'state_id' => 11],
                ['name' => "Ebonyi",           'state_id' => 11],
                ['name' => "Ivo",              'state_id' => 11]

            ]);

            //"Enugu"
            DB::table('lgas')->insert([

                ['name' =>    "Enugu South,",   'state_id' => 12],
                ['name' =>    "Igbo-Eze South", 'state_id' => 12],
                ['name' =>    "Enugu North",    'state_id' => 12],
                ['name' =>    "Nkanu",          'state_id' => 12],
                ['name' =>    "Udi Agwu",       'state_id' => 12],
                ['name' =>    "Oji-River",      'state_id' => 12],
                ['name' =>    "Ezeagu",         'state_id' => 12],
                ['name' =>    "IgboEze North",  'state_id' => 12],
                ['name' =>    "Isi-Uzo",        'state_id' => 12],
                ['name' =>    "Nsukka",         'state_id' => 12],
                ['name' =>    "Igbo-Ekiti",     'state_id' => 12],
                ['name' =>    "Uzo-Uwani",      'state_id' => 12],
                ['name' =>    "Enugu Eas",      'state_id' => 12],
                ['name' =>    "Aninri",         'state_id' => 12],
                ['name' =>    "Nkanu East",     'state_id' => 12],
                ['name' =>    "Udenu.",        'state_id' => 12]

            ]);

            //"Edo"
            DB::table('lgas')->insert([

                ['name' => "Esan North-East",  'state_id' => 13],
                ['name' => "Esan Central",     'state_id' => 13],
                ['name' => "Esan West",        'state_id' => 13],
                ['name' => "Egor",             'state_id' => 13],
                ['name' => "Ukpoba",           'state_id' => 13],
                ['name' => "Central",          'state_id' => 13],
                ['name' => "Etsako Central",   'state_id' => 13],
                ['name' => "Igueben",          'state_id' => 13],
                ['name' => "Oredo",            'state_id' => 13],
                ['name' => "Ovia SouthWest",   'state_id' => 13],
                ['name' => "Ovia South-East",  'state_id' => 13],
                ['name' => "Orhionwon",        'state_id' => 13],
                ['name' => "Uhunmwonde",       'state_id' => 13],
                ['name' => "Etsako East",      'state_id' => 13],
                ['name' => "Esan South-East",  'state_id' => 13]

            ]);

            //Ekiti
            DB::table('lgas')->insert([

                ['name' => "Ado",              'state_id' => 14],
                ['name' => "Ekiti-East",       'state_id' => 14],
                ['name' => "Ekiti-West",       'state_id' => 14],
                ['name' => "Emure/Ise/Orun",   'state_id' => 14],
                ['name' => "Ekiti South-West", 'state_id' => 14],
                ['name' => "Ikare",            'state_id' => 14],
                ['name' => "Irepodun",         'state_id' => 14],
                ['name' => "Ijero,",           'state_id' => 14],
                ['name' => "Ido/Osi",          'state_id' => 14],
                ['name' => "Oye",              'state_id' => 14],
                ['name' => "Ikole",            'state_id' => 14],
                ['name' => "Moba",             'state_id' => 14],
                ['name' => "Gbonyin",          'state_id' => 14],
                ['name' => "Efon",             'state_id' => 14],
                ['name' => "Ise/Orun",         'state_id' => 14],
                ['name' => "Ilejemeje.",       'state_id' => 14]

            ]);

            //"FCT - Abuja
            DB::table('lgas')->insert([

                ['name' =>  "Abaji",            'state_id' => 15],
                ['name' =>  "Abuja Municipal",  'state_id' => 15],
                ['name' =>  "Bwari",            'state_id' => 15],
                ['name' =>  "Gwagwalada",       'state_id' => 15],
                ['name' =>  "Kuje",             'state_id' => 15],
                ['name' =>  "Kwali",           'state_id' => 15]

            ]);

            //"Gombe"
            DB::table('lgas')->insert([

                ['name' =>       "Akko",           'state_id' => 16],
                ['name' =>       "Balanga",        'state_id' => 16],
                ['name' =>       "Billiri",        'state_id' => 16],
                ['name' =>       "Dukku",          'state_id' => 16],
                ['name' =>       "Kaltungo",       'state_id' => 16],
                ['name' =>       "Kwami",          'state_id' => 16],
                ['name' =>       "Shomgom",        'state_id' => 16],
                ['name' =>       "Funakaye",       'state_id' => 16],
                ['name' =>       "Gombe",          'state_id' => 16],
                ['name' =>       "Nafada/Bajoga",  'state_id' => 16],
                ['name' =>       "Yamaltu/Delta.", 'state_id' => 16]

            ]);

            //Imo
            DB::table('lgas')->insert([

                ['name' => "Aboh-Mbaise",      'state_id' => 17],
                ['name' => "Ahiazu-Mbaise",    'state_id' => 17],
                ['name' => "Ehime-Mbano",      'state_id' => 17],
                ['name' => "Ezinihitte",       'state_id' => 17],
                ['name' => "Ideato North",     'state_id' => 17],
                ['name' => "Ideato South",     'state_id' => 17],
                ['name' => "Ihitte/Uboma",     'state_id' => 17],
                ['name' => "Ikeduru",          'state_id' => 17],
                ['name' => "Isiala Mbano",     'state_id' => 17],
                ['name' => "Isu",              'state_id' => 17],
                ['name' => "Mbaitoli",         'state_id' => 17],
                ['name' => "Mbaitoli",         'state_id' => 17],
                ['name' => "Ngor-Okpala",      'state_id' => 17],
                ['name' => "Njaba",            'state_id' => 17],
                ['name' => "Nwangele",         'state_id' => 17],
                ['name' => "Nkwerre",          'state_id' => 17],
                ['name' => "Obowo",            'state_id' => 17],
                ['name' => "Oguta",            'state_id' => 17],
                ['name' => "Ohaji/Egbema",     'state_id' => 17],
                ['name' => "Okigwe",           'state_id' => 17],
                ['name' => "Orlu",             'state_id' => 17],
                ['name' => "Orsu",             'state_id' => 17],
                ['name' => "Oru East",         'state_id' => 17],
                ['name' => "Oru West",         'state_id' => 17],
                ['name' => "Owerri-Municipal", 'state_id' => 17],
                ['name' => "Owerri North",     'state_id' => 17],
                ['name' => "Owerri West",      'state_id' => 17]

            ]);

            //"Jigawa"
            DB::table('lgas')->insert([

                ['name' => "Auyo",           'state_id' => 18],
                ['name' => "Babura",         'state_id' => 18],
                ['name' => "Birni Kudu",     'state_id' => 18],
                ['name' => "Biriniwa",       'state_id' => 18],
                ['name' => "Buji",           'state_id' => 18],
                ['name' => "Dutse",          'state_id' => 18],
                ['name' => "Gagarawa",       'state_id' => 18],
                ['name' => "Garki",          'state_id' => 18],
                ['name' => "Gumel",          'state_id' => 18],
                ['name' => "Guri",           'state_id' => 18],
                ['name' => "Gwaram",         'state_id' => 18],
                ['name' => "Gwiwa",          'state_id' => 18],
                ['name' => "Hadejia",        'state_id' => 18],
                ['name' => "Jahun",          'state_id' => 18],
                ['name' => "Kafin Hausa",    'state_id' => 18],
                ['name' => "Kaugama Kazaure", 'state_id' => 18],
                ['name' => "Kiri Kasamma",   'state_id' => 18],
                ['name' => "Kiyawa",         'state_id' => 18],
                ['name' => "Maigatari",      'state_id' => 18],
                ['name' => "Malam Madori",   'state_id' => 18],
                ['name' => "Miga",           'state_id' => 18],
                ['name' => "Ringim",         'state_id' => 18],
                ['name' => "Roni",           'state_id' => 18],
                ['name' => "Sule-Tankarkar", 'state_id' => 18],
                ['name' => "Taura",          'state_id' => 18],
                ['name' => "Yankwashi",      'state_id' => 18]

            ]);

            //"Kaduna"
            DB::table('lgas')->insert([

                ['name' => "Birni-Gwari",   'state_id' => 19],
                ['name' => "Chikun",        'state_id' => 19],
                ['name' => "Giwa",          'state_id' => 19],
                ['name' => "Igabi",         'state_id' => 19],
                ['name' => "Ikara",         'state_id' => 19],
                ['name' => "jaba",          'state_id' => 19],
                ['name' => "Jema'a",        'state_id' => 19],
                ['name' => "Kachia",        'state_id' => 19],
                ['name' => "Kaduna North",  'state_id' => 19],
                ['name' => "Kaduna South",  'state_id' => 19],
                ['name' => "Kagarko",       'state_id' => 19],
                ['name' => "Kajuru",        'state_id' => 19],
                ['name' => "Kaura",         'state_id' => 19],
                ['name' => "Kauru",         'state_id' => 19],
                ['name' => "Kubau",         'state_id' => 19],
                ['name' => "Kudan",         'state_id' => 19],
                ['name' => "Lere",          'state_id' => 19],
                ['name' => "Makarfi",       'state_id' => 19],
                ['name' => "Sabon-Gari",    'state_id' => 19],
                ['name' => "Sanga",         'state_id' => 19],
                ['name' => "Soba",          'state_id' => 19],
                ['name' => "Zango-Kataf",   'state_id' => 19],
                ['name' => "Zaria",         'state_id' => 19]

            ]);

            //"Kano"
            DB::table('lgas')->insert([

                ['name' => "Ajingi",         'state_id' => 20],
                ['name' => "Albasu",         'state_id' => 20],
                ['name' => "Bagwai",         'state_id' => 20],
                ['name' => "Bebeji",         'state_id' => 20],
                ['name' => "Bichi",          'state_id' => 20],
                ['name' => "Bunkure",        'state_id' => 20],
                ['name' => "Dala",           'state_id' => 20],
                ['name' => "Dambatta",       'state_id' => 20],
                ['name' => "Dawakin Kudu",   'state_id' => 20],
                ['name' => "Dawakin Tofa",   'state_id' => 20],
                ['name' => "Doguwa",         'state_id' => 20],
                ['name' => "Fagge",          'state_id' => 20],
                ['name' => "Gabasawa",       'state_id' => 20],
                ['name' => "Garko",          'state_id' => 20],
                ['name' => "Garum",          'state_id' => 20],
                ['name' => "Mallam",         'state_id' => 20],
                ['name' => "Gaya",           'state_id' => 20],
                ['name' => "Gezawa",         'state_id' => 20],
                ['name' => "Gwale",          'state_id' => 20],
                ['name' => "Gwarzo",         'state_id' => 20],
                ['name' => "Kabo",           'state_id' => 20],
                ['name' => "Kano Municipal", 'state_id' => 20],
                ['name' => "Karaye",         'state_id' => 20],
                ['name' => "Kibiya",         'state_id' => 20],
                ['name' => "Kiru",           'state_id' => 20],
                ['name' => "kumbotso",       'state_id' => 20],
                ['name' => "Kunchi",         'state_id' => 20],
                ['name' => "Kura",           'state_id' => 20],
                ['name' => "Madobi",         'state_id' => 20],
                ['name' => "Makoda",         'state_id' => 20],
                ['name' => "Minjibir",       'state_id' => 20],
                ['name' => "Nasarawa",       'state_id' => 20],
                ['name' => "Rano",           'state_id' => 20],
                ['name' => "Rimin Gado",     'state_id' => 20],
                ['name' => "Rogo",           'state_id' => 20],
                ['name' => "Shanono",        'state_id' => 20],
                ['name' => "Sumaila",        'state_id' => 20],
                ['name' => "Takali",         'state_id' => 20],
                ['name' => "Tarauni",        'state_id' => 20],
                ['name' => "Tofa",           'state_id' => 20],
                ['name' => "Tsanyawa",       'state_id' => 20],
                ['name' => "Tudun Wada",     'state_id' => 20],
                ['name' => "Ungogo",         'state_id' => 20],
                ['name' => "Warawa",         'state_id' => 20],
                ['name' => "Wudil",          'state_id' => 20]

            ]);

            //"Katsina"
            DB::table('lgas')->insert([

                ['name' => "Bakori",      'state_id' => 21],
                ['name' => "Batagarawa",  'state_id' => 21],
                ['name' => "Batsari",     'state_id' => 21],
                ['name' => "Baure",       'state_id' => 21],
                ['name' => "Bindawa",     'state_id' => 21],
                ['name' => "Charanchi",   'state_id' => 21],
                ['name' => "Dandume",     'state_id' => 21],
                ['name' => "Danja",       'state_id' => 21],
                ['name' => "Dan Musa",    'state_id' => 21],
                ['name' => "Daura",       'state_id' => 21],
                ['name' => "Dutsi",       'state_id' => 21],
                ['name' => "Dutsin-Ma",   'state_id' => 21],
                ['name' => "Faskari",     'state_id' => 21],
                ['name' => "Funtua",      'state_id' => 21],
                ['name' => "Ingawa",      'state_id' => 21],
                ['name' => "Jibia",       'state_id' => 21],
                ['name' => "Kafur",       'state_id' => 21],
                ['name' => "Kaita",       'state_id' => 21],
                ['name' => "Kankara",     'state_id' => 21],
                ['name' => "Kankia",      'state_id' => 21],
                ['name' => "Katsina",     'state_id' => 21],
                ['name' => "Kurfi",       'state_id' => 21],
                ['name' => "Kusada",      'state_id' => 21],
                ['name' => "Mai'Adua",    'state_id' => 21],
                ['name' => "Malumfashi",  'state_id' => 21],
                ['name' => "Mani",        'state_id' => 21],
                ['name' => "Mashi",       'state_id' => 21],
                ['name' => "Matazuu",     'state_id' => 21],
                ['name' => "Musawa",      'state_id' => 21],
                ['name' => "Rimi",        'state_id' => 21],
                ['name' => "Sabuwa",      'state_id' => 21],
                ['name' => "Safana",      'state_id' => 21],
                ['name' => "Sandamu",     'state_id' => 21],
                ['name' => "Zango",       'state_id' => 21]

            ]);

            //"kebbi"
            DB::table('lgas')->insert([

                ['name' => "Aleiro",       'state_id' => 22],
                ['name' => "Arewa-Dandi",  'state_id' => 22],
                ['name' => "Argungu",      'state_id' => 22],
                ['name' => "Augie",        'state_id' => 22],
                ['name' => "Bagudo",       'state_id' => 22],
                ['name' => "Birnin Kebbi", 'state_id' => 22],
                ['name' => "Bunza",        'state_id' => 22],
                ['name' => "Dandi",        'state_id' => 22],
                ['name' => "Fakai",        'state_id' => 22],
                ['name' => "Gwandu",       'state_id' => 22],
                ['name' => "Jega",         'state_id' => 22],
                ['name' => "Kalgo",        'state_id' => 22],
                ['name' => "Koko/Besse",   'state_id' => 22],
                ['name' => "Maiyama",      'state_id' => 22],
                ['name' => "Ngaski",       'state_id' => 22],
                ['name' => "Sakaba",       'state_id' => 22],
                ['name' => "Shanga",       'state_id' => 22],
                ['name' => "Suru",         'state_id' => 22],
                ['name' => "Wasagu/Danko", 'state_id' => 22],
                ['name' => "Yauri",        'state_id' => 22],
                ['name' => "Zuru",         'state_id' => 22]

            ]);

            //"kogi"
            DB::table('lgas')->insert([

                ['name' => "Adavi",                'state_id' => 23],
                ['name' => "Ajaokuta",             'state_id' => 23],
                ['name' => "Ankpa",                'state_id' => 23],
                ['name' => "Bassa",                'state_id' => 23],
                ['name' => "Dekina",               'state_id' => 23],
                ['name' => "Ibaji",                'state_id' => 23],
                ['name' => "Idah",                 'state_id' => 23],
                ['name' => "Igalamela-Odolu",      'state_id' => 23],
                ['name' => "Ijumu",                'state_id' => 23],
                ['name' => "Kabba/Bunu",           'state_id' => 23],
                ['name' => "Kogi",                 'state_id' => 23],
                ['name' => "Lokoja",               'state_id' => 23],
                ['name' => "Mopa-Muro",            'state_id' => 23],
                ['name' => "Ofu",                  'state_id' => 23],
                ['name' => "Ogori/Mangongo",       'state_id' => 23],
                ['name' => "Okehi",                'state_id' => 23],
                ['name' => "Okene",                'state_id' => 23],
                ['name' => "Olamabolo",            'state_id' => 23],
                ['name' => "Omala",                'state_id' => 23],
                ['name' => "Yagba East",           'state_id' => 23],
                ['name' => "Yagba West",           'state_id' => 23]

            ]);

            //"kwara"
            DB::table('lgas')->insert([

                ['name' => "Asa",          'state_id' => 24],
                ['name' => "Baruten",      'state_id' => 24],
                ['name' => "Edu",          'state_id' => 24],
                ['name' => "Ekiti",        'state_id' => 24],
                ['name' => "Ifelodun",     'state_id' => 24],
                ['name' => "Ilorin East",  'state_id' => 24],
                ['name' => "Ilorin West",  'state_id' => 24],
                ['name' => "Irepodun",     'state_id' => 24],
                ['name' => "Isin",         'state_id' => 24],
                ['name' => "Kaiama",       'state_id' => 24],
                ['name' => "Moro",         'state_id' => 24],
                ['name' => "Offa",         'state_id' => 24],
                ['name' => "Oke-Ero",      'state_id' => 24],
                ['name' => "Oyun",         'state_id' => 24],
                ['name' => "Pategi",       'state_id' => 24]

            ]);

            //"Lagos"
            DB::table('lgas')->insert([

                ['name' => "Agege",            'state_id' => 25],
                ['name' => "Ajeromi-Ifelodun", 'state_id' => 25],
                ['name' => "Alimosho",         'state_id' => 25],
                ['name' => "Amuwo-Odofin",     'state_id' => 25],
                ['name' => "Apapa",            'state_id' => 25],
                ['name' => "Badagry",          'state_id' => 25],
                ['name' => "Epe",              'state_id' => 25],
                ['name' => "Eti-Osa",          'state_id' => 25],
                ['name' => "Ibeju/Lekki",      'state_id' => 25],
                ['name' => "Ifako-Ijaye",      'state_id' => 25],
                ['name' => "Ikeja",            'state_id' => 25],
                ['name' => "Ikorodu",          'state_id' => 25],
                ['name' => "Kosofe",           'state_id' => 25],
                ['name' => "Lagos Island",     'state_id' => 25],
                ['name' => "Lagos Mainland",   'state_id' => 25],
                ['name' => "Mushin",           'state_id' => 25],
                ['name' => "Ojo",              'state_id' => 25],
                ['name' => "Oshodi-Isolo",     'state_id' => 25],
                ['name' => "Shomolu",          'state_id' => 25],
                ['name' => "Surulere",         'state_id' => 25]

            ]);

            //"Nasarawa"
            DB::table('lgas')->insert([

                ['name' => "Akwanga",            'state_id' => 26],
                ['name' => "Awe",                'state_id' => 26],
                ['name' => "Doma",               'state_id' => 26],
                ['name' => "Karu",               'state_id' => 26],
                ['name' => "Keana",              'state_id' => 26],
                ['name' => "Keffi",              'state_id' => 26],
                ['name' => "Kokona",             'state_id' => 26],
                ['name' => "Lafia",              'state_id' => 26],
                ['name' => "Nasarawa",           'state_id' => 26],
                ['name' => "Nasarawa-Eggon",     'state_id' => 26],
                ['name' => "Obi",                'state_id' => 26],
                ['name' => "Toto",               'state_id' => 26],
                ['name' => "Wamba",              'state_id' => 26]

            ]);

            //"Niger"
            DB::table('lgas')->insert([

                ['name' => "Agaie",     'state_id' => 27],
                ['name' => "Agwara",    'state_id' => 27],
                ['name' => "Bida",      'state_id' => 27],
                ['name' => "Borgu",     'state_id' => 27],
                ['name' => "Bosso",     'state_id' => 27],
                ['name' => "Chanchaga", 'state_id' => 27],
                ['name' => "Edati",     'state_id' => 27],
                ['name' => "Gbako",     'state_id' => 27],
                ['name' => "Gurara",    'state_id' => 27],
                ['name' => "Katcha",    'state_id' => 27],
                ['name' => "Kontagora", 'state_id' => 27],
                ['name' => "Lapai",     'state_id' => 27],
                ['name' => "Lavun",     'state_id' => 27],
                ['name' => "Magama",    'state_id' => 27],
                ['name' => "Mariga",    'state_id' => 27],
                ['name' => "Mashegu",   'state_id' => 27],
                ['name' => "Mokwa",     'state_id' => 27],
                ['name' => "Muya",      'state_id' => 27],
                ['name' => "Pailoro",   'state_id' => 27],
                ['name' => "Rafi",      'state_id' => 27],
                ['name' => "Rijau",     'state_id' => 27],
                ['name' => "Shiroro",   'state_id' => 27],
                ['name' => "Suleja",    'state_id' => 27],
                ['name' => "Tafa",      'state_id' => 27],
                ['name' => "Wushishi",  'state_id' => 27]

            ]);

            //"Ogun"
            DB::table('lgas')->insert([

                ['name' => "Abeokuta North",   'state_id' => 28],
                ['name' => "Abeokuta South",   'state_id' => 28],
                ['name' => "Ado-Odo/Ota",      'state_id' => 28],
                ['name' => "Egbado North",     'state_id' => 28],
                ['name' => "Egbado South",     'state_id' => 28],
                ['name' => "Ewekoro",          'state_id' => 28],
                ['name' => "Ifo",              'state_id' => 28],
                ['name' => "Ijebu East",       'state_id' => 28],
                ['name' => "Ijebu North",      'state_id' => 28],
                ['name' => "Ijebu North East", 'state_id' => 28],
                ['name' => "Ijebu Ode",        'state_id' => 28],
                ['name' => "Ikenne",           'state_id' => 28],
                ['name' => "Imeko-Afon",       'state_id' => 28],
                ['name' => "Ipokia",           'state_id' => 28],
                ['name' => "Obafemi-Owode",    'state_id' => 28],
                ['name' => "Ogun Waterside",   'state_id' => 28],
                ['name' => "Odeda",            'state_id' => 28],
                ['name' => "Odogbolu",         'state_id' => 28],
                ['name' => "Remo North",       'state_id' => 28],
                ['name' => "Shagamu",          'state_id' => 28]

            ]);

            //"Ondo"
            DB::table('lgas')->insert([

                ['name' => "Akoko North East",       'state_id' => 29],
                ['name' => "Akoko North West",       'state_id' => 29],
                ['name' => "Akoko South Akure East", 'state_id' => 29],
                ['name' => "Akoko South West",       'state_id' => 29],
                ['name' => "Akure North",            'state_id' => 29],
                ['name' => "Akure South",            'state_id' => 29],
                ['name' => "Ese-Odo",                'state_id' => 29],
                ['name' => "Idanre",                 'state_id' => 29],
                ['name' => "Ifedore",                'state_id' => 29],
                ['name' => "Ilaje",                  'state_id' => 29],
                ['name' => "Ile-Oluji",              'state_id' => 29],
                ['name' => "Okeigbo",                'state_id' => 29],
                ['name' => "Irele",                  'state_id' => 29],
                ['name' => "Odigbo",                 'state_id' => 29],
                ['name' => "Okitipupa",              'state_id' => 29],
                ['name' => "Ondo East",              'state_id' => 29],
                ['name' => "Ondo West",              'state_id' => 29],
                ['name' => "Ose",                    'state_id' => 29],
                ['name' => "Owo",                    'state_id' => 29]

            ]);

            //"Osun"
            DB::table('lgas')->insert([

                ['name' => "Aiyedade",       'state_id' => 30],
                ['name' => "Aiyedire",       'state_id' => 30],
                ['name' => "Atakumosa East", 'state_id' => 30],
                ['name' => "Atakumosa West", 'state_id' => 30],
                ['name' => "Boluwaduro",     'state_id' => 30],
                ['name' => "Boripe",         'state_id' => 30],
                ['name' => "Ede North",      'state_id' => 30],
                ['name' => "Ede South",      'state_id' => 30],
                ['name' => "Egbedore",       'state_id' => 30],
                ['name' => "Ejigbo",         'state_id' => 30],
                ['name' => "Ife Central",    'state_id' => 30],
                ['name' => "Ife East",       'state_id' => 30],
                ['name' => "Ife North",      'state_id' => 30],
                ['name' => "Ife South",      'state_id' => 30],
                ['name' => "Ifedayo",        'state_id' => 30],
                ['name' => "Ifelodun",       'state_id' => 30],
                ['name' => "Ila",            'state_id' => 30],
                ['name' => "Ilesha East",    'state_id' => 30],
                ['name' => "Ilesha West",    'state_id' => 30],
                ['name' => "Irepodun",       'state_id' => 30],
                ['name' => "Irewole",        'state_id' => 30],
                ['name' => "Isokan",         'state_id' => 30],
                ['name' => "Iwo",            'state_id' => 30],
                ['name' => "Obokun",         'state_id' => 30],
                ['name' => "Odo-Otin",       'state_id' => 30],
                ['name' => "Ola-Oluwa",      'state_id' => 30],
                ['name' => "Olorunda",       'state_id' => 30],
                ['name' => "Oriade",         'state_id' => 30],
                ['name' => "Orolu",          'state_id' => 30],
                ['name' => "Osogbo",         'state_id' => 30]

            ]);

            //"Oyo"
            DB::table('lgas')->insert([

                ['name' => "Afijio",                 'state_id' => 31],
                ['name' => "Akinyele",               'state_id' => 31],
                ['name' => "Atiba",                  'state_id' => 31],
                ['name' => "Atigbo",                 'state_id' => 31],
                ['name' => "Egbeda",                 'state_id' => 31],
                ['name' => "Ibadan Central",         'state_id' => 31],
                ['name' => "Ibadan North",           'state_id' => 31],
                ['name' => "Ibadan North West",      'state_id' => 31],
                ['name' => "Ibadan South East",      'state_id' => 31],
                ['name' => "Ibadan South West",      'state_id' => 31],
                ['name' => "Ibarapa Central",        'state_id' => 31],
                ['name' => "Ibarapa East",           'state_id' => 31],
                ['name' => "Ibarapa North",          'state_id' => 31],
                ['name' => "Ido",                    'state_id' => 31],
                ['name' => "Irepo",                  'state_id' => 31],
                ['name' => "Iseyin",                 'state_id' => 31],
                ['name' => "Itesiwaju",              'state_id' => 31],
                ['name' => "Iwajowa",                'state_id' => 31],
                ['name' => "Kajola",                 'state_id' => 31],
                ['name' => "Lagelu Ogbomosho North", 'state_id' => 31],
                ['name' => "Ogbmosho South",         'state_id' => 31],
                ['name' => "Ogo Oluwa",              'state_id' => 31],
                ['name' => "Olorunsogo",             'state_id' => 31],
                ['name' => "Oluyole",                'state_id' => 31],
                ['name' => "Ona-Ara",                'state_id' => 31],
                ['name' => "Orelope",                'state_id' => 31],
                ['name' => "Ori Ire",                'state_id' => 31],
                ['name' => "Oyo East",               'state_id' => 31],
                ['name' => "Oyo West",               'state_id' => 31],
                ['name' => "Saki East",              'state_id' => 31],
                ['name' => "Saki West",              'state_id' => 31],
                ['name' => "Surulere",               'state_id' => 31]

            ]);

            //"{Plataeu}"
            DB::table('lgas')->insert([

                ['name' => "Barikin Ladi",   'state_id' => 32],
                ['name' => "Bassa",          'state_id' => 32],
                ['name' => "Bokkos",         'state_id' => 32],
                ['name' => "Jos East",       'state_id' => 32],
                ['name' => "Jos North",      'state_id' => 32],
                ['name' => "Jos South",      'state_id' => 32],
                ['name' => "Kanam",          'state_id' => 32],
                ['name' => "Kanke",          'state_id' => 32],
                ['name' => "Langtang North", 'state_id' => 32],
                ['name' => "Langtang South", 'state_id' => 32],
                ['name' => "Mangu",          'state_id' => 32],
                ['name' => "Mikang",         'state_id' => 32],
                ['name' => "Pankshin",       'state_id' => 32],
                ['name' => "Qua'an Pan",     'state_id' => 32],
                ['name' => "Riyom",          'state_id' => 32],
                ['name' => "Shendam",        'state_id' => 32],
                ['name' => "Wase",           'state_id' => 32]

            ]);

            //"Rivers"
            DB::table('lgas')->insert([

                ['name' => "Abua/Odual",        'state_id' => 33],
                ['name' => "Ahoada East",       'state_id' => 33],
                ['name' => "Ahoada West",       'state_id' => 33],
                ['name' => "Akuku Toru",        'state_id' => 33],
                ['name' => "Andoni",            'state_id' => 33],
                ['name' => "Asari-Toru",        'state_id' => 33],
                ['name' => "Bonny",             'state_id' => 33],
                ['name' => "Degema",            'state_id' => 33],
                ['name' => "Emohua",            'state_id' => 33],
                ['name' => "Eleme",             'state_id' => 33],
                ['name' => "Etche",             'state_id' => 33],
                ['name' => "Gokana",            'state_id' => 33],
                ['name' => "Ikwerre",           'state_id' => 33],
                ['name' => "Khana",             'state_id' => 33],
                ['name' => "Obia/Akpor",        'state_id' => 33],
                ['name' => "Ogba/Egbema/Ndoni", 'state_id' => 33],
                ['name' => "Ogu/Bolo",          'state_id' => 33],
                ['name' => "Okrika",            'state_id' => 33],
                ['name' => "Omumma",            'state_id' => 33],
                ['name' => "Opobo/Nkoro",       'state_id' => 33],
                ['name' => "Oyigbo",            'state_id' => 33],
                ['name' => "Port-Harcourt",     'state_id' => 33],
                ['name' => "Tai",               'state_id' => 33]

            ]);

            //"Sokoto"
            DB::table('lgas')->insert([

                ['name' => "Binji",        'state_id' => 34],
                ['name' => "Bodinga",      'state_id' => 34],
                ['name' => "Dange-shnsi",  'state_id' => 34],
                ['name' => "Gada",         'state_id' => 34],
                ['name' => "Goronyo",      'state_id' => 34],
                ['name' => "Gudu",         'state_id' => 34],
                ['name' => "Gawabawa",     'state_id' => 34],
                ['name' => "Illela",       'state_id' => 34],
                ['name' => "Isa",          'state_id' => 34],
                ['name' => "Kware",        'state_id' => 34],
                ['name' => "kebbe",        'state_id' => 34],
                ['name' => "Rabah",        'state_id' => 34],
                ['name' => "Sabon birni",  'state_id' => 34],
                ['name' => "Shagari",      'state_id' => 34],
                ['name' => "Silame",       'state_id' => 34],
                ['name' => "Sokoto North", 'state_id' => 34],
                ['name' => "Sokoto South", 'state_id' => 34],
                ['name' => "Tambuwal",     'state_id' => 34],
                ['name' => "Tqngaza",      'state_id' => 34],
                ['name' => "Tureta",       'state_id' => 34],
                ['name' => "Wamako",       'state_id' => 34],
                ['name' => "Wurno",        'state_id' => 34],
                ['name' => "Yabo",         'state_id' => 34]

            ]);

            //"Taraba"
            DB::table('lgas')->insert([

                ['name' => "Ardo-kola",    'state_id' => 35],
                ['name' => "Bali",         'state_id' => 35],
                ['name' => "Donga",        'state_id' => 35],
                ['name' => "Gashaka",      'state_id' => 35],
                ['name' => "Cassol",       'state_id' => 35],
                ['name' => "Ibi",          'state_id' => 35],
                ['name' => "Jalingo",      'state_id' => 35],
                ['name' => "Karin-Lamido", 'state_id' => 35],
                ['name' => "Kurmi",        'state_id' => 35],
                ['name' => "Lau",          'state_id' => 35],
                ['name' => "Sardauna",     'state_id' => 35],
                ['name' => "Takum",        'state_id' => 35],
                ['name' => "Ussa",         'state_id' => 35],
                ['name' => "Wukari",       'state_id' => 35],
                ['name' => "Yorro",        'state_id' => 35],
                ['name' => "Zing",         'state_id' => 35]

            ]);

            //"Yobe"
            DB::table('lgas')->insert([

                ['name' => "Bade",           'state_id' => 36],
                ['name' => "Bursari",        'state_id' => 36],
                ['name' => "Damaturu",       'state_id' => 36],
                ['name' => "Fika",           'state_id' => 36],
                ['name' => "Fune",           'state_id' => 36],
                ['name' => "Geidam",         'state_id' => 36],
                ['name' => "Gujba",          'state_id' => 36],
                ['name' => "Gulani",         'state_id' => 36],
                ['name' => "Jakusko",        'state_id' => 36],
                ['name' => "Karasuwa",       'state_id' => 36],
                ['name' => "Karawa",         'state_id' => 36],
                ['name' => "Machina",        'state_id' => 36],
                ['name' => "Nangere",        'state_id' => 36],
                ['name' => "Nguru Potiskum", 'state_id' => 36],
                ['name' => "Tarmua",         'state_id' => 36],
                ['name' => "Yunusari",       'state_id' => 36],
                ['name' => "Yusufari",       'state_id' => 36]

            ]);

            //"Zamfara"
            DB::table('lgas')->insert([

                ['name' => "Anka",          'state_id' => 37],
                ['name' => "Bakura",        'state_id' => 37],
                ['name' => "Birnin Magaji", 'state_id' => 37],
                ['name' => "Bukkuyum",      'state_id' => 37],
                ['name' => "Bungudu",       'state_id' => 37],
                ['name' => "Gummi",         'state_id' => 37],
                ['name' => "Gusau",         'state_id' => 37],
                ['name' => "Kaura",         'state_id' => 37],
                ['name' => "Namoda",        'state_id' => 37],
                ['name' => "Maradun",       'state_id' => 37],
                ['name' => "Maru",          'state_id' => 37],
                ['name' => "Shinkafi",      'state_id' => 37],
                ['name' => "Talata Mafara", 'state_id' => 37],
                ['name' => "Tsafe",         'state_id' => 37],
                ['name' => "Zurmi",         'state_id' => 37]

            ]);
        }
    }
}
