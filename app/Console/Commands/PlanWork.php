<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;
use Cache;
use Psr\SimpleCache\InvalidArgumentException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class PlanWork
 */
class PlanWork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cattr:demo:plan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cattr Planning of the work (only for demo)';

    // 1440 - limit of the time at a day

    private array $plans = [
        [['start' => 540, 'end' => 780], ['start' => 840, 'end' => 1080]],
        [['start' => 600, 'end' => 780], ['start' => 840, 'end' => 1140]],
        [
            ['start' => 480, 'end' => 570],
            ['start' => 582, 'end' => 672],
            ['start' => 684, 'end' => 774],
            ['start' => 786, 'end' => 876],
            ['start' => 888, 'end' => 978],
            ['start' => 990, 'end' => 1020]
        ],
        [['start' => 600, 'end' => 720], ['start' => 835, 'end' => 1105], ['start' => 1165, 'end' => 1255]],
        [
            ['start' => 480, 'end' => 600],
            ['start' => 620, 'end' => 720],
            ['start' => 780, 'end' => 900],
            ['start' => 915, 'end' => 1020]
        ],
        [['start' => 780, 'end' => 990], ['start' => 1065, 'end' => 1275]],
        [],
        [],

        [
            ['start' => 107, 'end' => 155],
            ['start' => 365, 'end' => 428],
            ['start' => 707, 'end' => 728],
            ['start' => 941, 'end' => 975],
            ['start' => 1219, 'end' => 1241]
        ],
        [
            ['start' => 288, 'end' => 295],
            ['start' => 389, 'end' => 564],
            ['start' => 603, 'end' => 679],
            ['start' => 684, 'end' => 689],
            ['start' => 752, 'end' => 845],
            ['start' => 854, 'end' => 1114],
            ['start' => 1390, 'end' => 1397],
            ['start' => 1429, 'end' => 102]
        ],
        [
            ['start' => 136, 'end' => 183],
            ['start' => 424, 'end' => 434],
            ['start' => 560, 'end' => 621],
            ['start' => 862, 'end' => 906],
            ['start' => 1171, 'end' => 1194],
            ['start' => 1204, 'end' => 1356],
            ['start' => 1363, 'end' => 110]
        ],
        [
            ['start' => 89, 'end' => 201],
            ['start' => 303, 'end' => 417],
            ['start' => 620, 'end' => 660],
            ['start' => 688, 'end' => 914],
            ['start' => 1112, 'end' => 1194],
            ['start' => 1403, 'end' => 1410]
        ],
        [
            ['start' => 171, 'end' => 224],
            ['start' => 249, 'end' => 486],
            ['start' => 674, 'end' => 744],
            ['start' => 920, 'end' => 971],
            ['start' => 1057, 'end' => 1123],
            ['start' => 1357, 'end' => 1379]
        ],
        [
            ['start' => 95, 'end' => 135],
            ['start' => 357, 'end' => 381],
            ['start' => 545, 'end' => 630],
            ['start' => 648, 'end' => 734],
            ['start' => 996, 'end' => 1021],
            ['start' => 1282, 'end' => 1288]
        ],
        [
            ['start' => 299, 'end' => 299],
            ['start' => 599, 'end' => 599],
            ['start' => 687, 'end' => 858],
            ['start' => 1119, 'end' => 1122],
            ['start' => 1378, 'end' => 1389]
        ],
        [
            ['start' => 211, 'end' => 269],
            ['start' => 298, 'end' => 346],
            ['start' => 353, 'end' => 531],
            ['start' => 747, 'end' => 810],
            ['start' => 1003, 'end' => 1092],
            ['start' => 1105, 'end' => 1372]
        ],
        [
            ['start' => 140, 'end' => 193],
            ['start' => 451, 'end' => 465],
            ['start' => 579, 'end' => 700],
            ['start' => 741, 'end' => 983],
            ['start' => 1195, 'end' => 1280],
            ['start' => 1428, 'end' => 118]
        ],
        [
            ['start' => 225, 'end' => 294],
            ['start' => 474, 'end' => 503],
            ['start' => 554, 'end' => 699],
            ['start' => 714, 'end' => 852],
            ['start' => 915, 'end' => 989],
            ['start' => 1196, 'end' => 1285],
            ['start' => 1427, 'end' => 139]
        ],
        [
            ['start' => 92, 'end' => 217],
            ['start' => 463, 'end' => 463],
            ['start' => 759, 'end' => 762],
            ['start' => 813, 'end' => 817],
            ['start' => 977, 'end' => 1006],
            ['start' => 1095, 'end' => 1113],
            ['start' => 1265, 'end' => 1318]
        ],
        [
            ['start' => 200, 'end' => 252],
            ['start' => 305, 'end' => 323],
            ['start' => 540, 'end' => 585],
            ['start' => 694, 'end' => 878],
            ['start' => 955, 'end' => 1177],
            ['start' => 1405, 'end' => 22]
        ],
        [
            ['start' => 32, 'end' => 174],
            ['start' => 435, 'end' => 467],
            ['start' => 582, 'end' => 702],
            ['start' => 916, 'end' => 977],
            ['start' => 1134, 'end' => 1204],
            ['start' => 1261, 'end' => 1421]
        ],
        [
            ['start' => 222, 'end' => 223],
            ['start' => 405, 'end' => 405],
            ['start' => 507, 'end' => 572],
            ['start' => 744, 'end' => 862],
            ['start' => 1078, 'end' => 1094],
            ['start' => 1270, 'end' => 1283]
        ],
        [
            ['start' => 220, 'end' => 283],
            ['start' => 326, 'end' => 581],
            ['start' => 834, 'end' => 859],
            ['start' => 1140, 'end' => 1150],
            ['start' => 1231, 'end' => 1378]
        ],
        [
            ['start' => 38, 'end' => 148],
            ['start' => 260, 'end' => 423],
            ['start' => 568, 'end' => 659],
            ['start' => 849, 'end' => 921],
            ['start' => 1208, 'end' => 1216]
        ]
    ];

    /**
     * Execute the console command.
     *
     * @return int
     * @throws InvalidArgumentException
     */
    public function handle(): int
    {
        $users = User::where(['is_admin' => false])->get()->toArray();

        $plan = [];
        $plans = $this->plans;

        while (count($users)) {
            $user = $this->getRandomValue($users);

            $tasks = Task::whereHas('users', function (Builder $query) use ($user) {
                $query->where('id', '=', $user['id']);
            })->get()->toArray();

            if (!count($tasks)) {
                continue;
            }

            $plan[] = [
                'user' => $user['id'],
                'intervals' => array_map(static function ($el) use ($tasks) {
                    shuffle($tasks);

                    $el['task'] = array_pop($tasks)['id'];

                    return $el;
                }, $this->getRandomValue($plans)),
            ];
        }

        Cache::set('usersPlan', $plan);

        return 0;
    }

    /**
     * @param $array
     * @return mixed
     */
    public function getRandomValue(&$array): mixed
    {
        shuffle($array);
        return array_pop($array);
    }
}
