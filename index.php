<?php
require_once './vendor/autoload.php';
require_once './config.php';

// Create and configure Slim app
$app = new \Slim\App(
    [
        'db'       => new medoo(
            [
                'database_type' => 'mariadb',
                'server'        => $config['db']['host'],
                'database_name' => $config['db']['database'],
                'username'      => $config['db']['user'],
                'password'      => $config['db']['password'],
                'charset'       => 'utf8',
            ]
        ),
        'config'   => $config,
        'settings' => [
            'displayErrorDetails' => true,
        ],
    ]
);

//private function uuid()
//{
//    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
//        mt_rand(0, 0xffff),
//        mt_rand(0, 0xffff),
//        mt_rand(0, 0xffff),
//        mt_rand(0, 0x0fff) | 0x4000,
//        mt_rand(0, 0x3fff) | 0x8000,
//        mt_rand(0, 0xffff),
//        mt_rand(0, 0xffff),
//        mt_rand(0, 0xffff));
//}

// Define app routes
$app->get(
    '/{format}/{title}/{volume}',
    function ($request, $response, $args) {
        /** @var \Slim\Http\Request $request */
        /** @var \Slim\Http\Response $response */

        $format        = strtolower($args['format']);
        $project_alias = $args['title'];
        $volume_alias  = $args['volume'];
        $height        = intval($request->getParam('pic', 1080));

        /** @var medoo $db */
        $db     = $this->db;
        $volume = $db->get(
            'volumes',
            [
                'volume_id',
                'project_id',
                'image_one',
                'name_file',
                'name_title',
                'name_jp',
                'name_en',
                'name_ru',
                'sequence_number',
                'author',
                'illustrator',
                'ISBN',
                'annotation',
            ],
            ['url' => $project_alias . '/' . $volume_alias]
        );

        if (!$volume) {
            return $response->withStatus(404);
        }

        $project     = $db->get('projects', ['url', 'title'], ['project_id' => $volume['project_id']]);
        $cover       = $db->get('external_resources', 'url', ['resource_id' => [$volume['image_one']]]);
        $touched     = $db->max(
            'texts_history',
            [
                '[>]texts'    => ['current_text_id' => 'text_id'],
                '[>]chapters' => ['current_text_id' => 'text_id'],
            ],
            'insertion_time',
            ['volume_id' => $volume['volume_id']]
        );
        $touched     = intval($touched) ?: 0;
        $activities  = $db->select(
            'volume_release_activities',
            [
                '[>]volume_activities(va)' => 'activity_id',
                '[>]team_members(tmm)'     => 'member_id',
                '[>]teams(tm)'             => ['tmm.team_id' => 'team_id'],
            ],
            ['release_activity_id', 'activity_id', 'activity_name', 'team_name', 'nikname(nickname)', 'team_hidden'],
            ['volume_id' => $volume['volume_id']]
        );
        $teams       = [];
        $translators = [];
        $workers     = [];
        foreach ($activities as $activity) {
            $is_translator = in_array($activity['activity_id'], [1, 2, 10, 11, 13]);
            if (!$activity['team_hidden']) {
                $teams[$activity['team_name']] -= $is_translator;
            }
            if ($is_translator) {
                $translators[$activity['nickname']] = null;
            }
            $workers[$activity['activity_name']][] = $activity['nickname'];
        }
        asort($teams);
        $teams = array_keys($teams);
        $translators = array_keys($translators);
        sort($translators);
        $texts = $db->select(
            'texts',
            [
                '[>]chapters' => 'text_id',
                '[>]volumes'  => 'volume_id',
            ],
            [
                'title',
                'text_html',
                'footnotes'
            ],
            [
                'volume_id' => $volume['volume_id'],
            ]
        );

        $text      = '';
        $footnotes = '';

        for ($i = 0; $i < sizeof($texts); $i++) {
            $text .= "<h2>" . $texts[$i]['title'] . "</h2>";
            $text .= $texts[$i]['text_html'];
            $footnotes .= $texts[$i]['footnotes'];
            if ($i < sizeof($texts) - 1) {
                $footnotes .= ',;,';
            }
        }

        $pdb       = [
            'annotation'   => $volume['annotation'],
            'author'       => $volume['author'],
            'name_ru'      => $volume['name_ru'],
            'illustrator'  => $volume['illustrator'],
            'cover'        => $cover,
            'translators'  => $translators,
            'series_title' => $project['title'],
            'series_num'   => $volume['sequence_number'],
            'revcount'     => 0, // watafak?
            'isbn'         => $volume['ISBN'],
            'command'      => implode(' совместно с ', $teams),
            'touched'      => $touched,
            'name_url'     => $project['url'],
            'name_main'    => $volume['name_file'],
            'workers'      => $workers,
            'footnotes'    => $footnotes,
        ];
        $converter = null;
        switch ($format) {
            case 'fb2':
                $converter = new Ruranobe\Converters\Fb2Converter($height, $pdb, $text, $this->config);
                break;
            case 'epub':
                $converter = new Ruranobe\Converters\EpubConverter($height, $pdb, $text, $this->config);
                break;
            case 'docx':
                $converter = new Ruranobe\Converters\DocxConverter($height, $pdb, $text, $this->config);
                break;
            default:
                return $response->withStatus(404, 'Unknown format');
        }

//        if (!empty($_SERVER['HTTP_REFERER'])) {
//            if (strrpos($_SERVER['HTTP_REFERER'], "ranobeclub")) {
//                header('Location: ' . "http://ruranobe.ru/r/" . array_shift(explode('/', $page)));
//                exit;
//            }
//            if (!strrpos($_SERVER['HTTP_REFERER'], "ruranobe.ru") &&
//                !strrpos($_SERVER['HTTP_REFERER'], "vk.com") &&
//                !strrpos($_SERVER['HTTP_REFERER'], "paveliarch.gnudip.de")
//            ) {
//                header('Location: ' . "http://ruranobe.ru/r/" . $page);
//                exit;
//            }
//        }
//        if ($request->getcookie('cid')) {
//            $cid = ($request->getcookie('cid'));
//        } else {
//            $cid = $this->uuid();
//            setcookie('cid', $cid);
//        }
//        $cid = urlencode($cid);
//        $ga = "http://www.google-analytics.com/collect?v=1&tid={$this->config['ga_tid']}&cid={$cid}&uip=" . urlencode($request->getIP()) . "&ua=" . urlencode($_SERVER['HTTP_USER_AGENT']);
//        if (isset($_SERVER['HTTP_REFERER']))
//            $ga .= "&dr=" . urlencode($_SERVER['HTTP_REFERER']);
//        if ($wgUser->getId())
//            $ga .= '&uid=' . $wgUser->getId();
//        file_get_contents($ga . "&t=pageview&dh=ruranobe.ru&dp=" . urlencode("/d/$format/$page"));
//        file_get_contents($ga . "&t=event&ec=download&ea=$format&el=" . urlencode($page));

        return $converter->convert($response);
        //return $response->withJson($pdb);
    }
);

// Run app
$app->run();