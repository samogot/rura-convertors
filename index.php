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
                'image_two',
                'image_three',
                'image_four',
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

        $project     = $db->get('projects', ['title'], ['project_id' => $volume['project_id']]);
        $touched     = $db->max(
            'texts_history',
            [
                '[>]chapters' => ['current_text_id' => 'text_id'],
            ],
            'insertion_time',
            ['volume_id' => $volume['volume_id']]
        );
        $touched     = strtotime($touched) ?: time();
        $activities  = $db->select(
            'volume_release_activities',
            [
                '[>]volume_activities(va)' => 'activity_id',
                '[>]team_members(tmm)'     => 'member_id',
                '[>]teams(tm)'             => ['tmm.team_id' => 'team_id'],
            ],
            ['release_activity_id', 'activity_id', 'activity_name', 'team_name', 'nickname', 'team_hidden'],
            ['volume_id' => $volume['volume_id']]
        );
        $teams       = [];
        $translators = [];
        $workers     = [];
        foreach ($activities as $activity) {
            $is_translator = in_array($activity['activity_id'], [1, 2, 10, 11, 13]);
            if (!$activity['team_hidden']) {
                if (!array_key_exists($activity['team_name'], $teams)) {
                    $teams[$activity['team_name']] = 0;
                }
                $teams[$activity['team_name']] -= $is_translator;
            }
            if ($is_translator) {
                $translators[$activity['nickname']] = null;
            }
            $workers[$activity['activity_name']][] = $activity['nickname'];
        }
        asort($teams);
        $teams       = array_keys($teams);
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
                'nested',
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
            $heading = $texts[$i]['nested'] ? 'h3' : 'h2';
            if ($texts[$i]['title'] != 'Начальные иллюстрации') {
                $text .= "<$heading>" . $texts[$i]['title'] . "</$heading>";
            }
            $text .= $texts[$i]['text_html'];
            $footnotes .= $texts[$i]['footnotes'];
            if ($i < sizeof($texts) - 1) {
                $footnotes .= ',;,';
            }
        }

        preg_match_all('/data-resource-id="(\d+)"/', $text, $matches);
        unset($matches[0]);
        $covers = [];
        if ($volume['image_one']) {
            $matches[1][] = $volume['image_one'];
            $covers[]     = $volume['image_one'];
        }
        if ($volume['image_two']) {
            $matches[1][] = $volume['image_two'];
            $covers[]     = $volume['image_two'];
        }
        if ($volume['image_three']) {
            $matches[1][] = $volume['image_three'];
            $covers[]     = $volume['image_three'];
        }
        if ($volume['image_four']) {
            $matches[1][] = $volume['image_four'];
            $covers[]     = $volume['image_four'];
        }
        if ($matches[1]) {
            $images_temp =
                $db->select(
                    'external_resources',
                    [
                        'resource_id',
                        'mime_type',
                        'url',
                        'thumbnail',
                        'width',
                        'height',
                        'title'
                    ],
                    [
                        'resource_id' => $matches[1],
                    ]
                );
        } else {
            $images_temp = [];
        }

        $images = array();
        for ($i = 0; $i < sizeof($images_temp); $i++) {
            $image         = $images_temp[$i];
            $convertWidth  = floor($height * $image['width'] / $image['height']);
            $convertWidth  = min($convertWidth, $image['width']);
            $convertHeight = min($image['height'], $height);
            $thumbnail     = sprintf($image['thumbnail'], $convertWidth);
            if (strpos($thumbnail, $this->config['repo_prefix']) === 0) {
                $file_path = $this->config['repo_prefix'] . substr($thumbnail, strlen($this->config['repo_prefix']));
                if (is_readable($file_path)) {
                    $image['thumbnail'] = $file_path;
                } else {
                    $image['thumbnail'] = 'http:' . $thumbnail;
                }
            } else {
                $image['thumbnail'] = $thumbnail;
            }
            $images[$image['resource_id']] = [
                'mime_type'      => $image['mime_type'],
                'url'            => $image['url'],
                'thumbnail'      => $image['thumbnail'],
                'width'          => $image['width'],
                'height'         => $image['height'],
                'convert_width'  => $convertWidth,
                'convert_height' => $convertHeight,
                'title'          => $image['title']
            ];
        }

        $pdb       = [
            'annotation'   => $volume['annotation'],
            'author'       => $volume['author'],
            'name_ru'      => $volume['name_ru'],
            'illustrator'  => $volume['illustrator'],
            'covers'       => $covers,
            'translators'  => $translators,
            'series_title' => $project['title'],
            'series_num'   => $volume['sequence_number'],
            'revcount'     => 0, // watafak?
            'isbn'         => $volume['ISBN'],
            'command'      => implode(' совместно с ', $teams),
            'touched'      => $touched,
            'name_url'     => $project_alias . '/' . $volume_alias,
            'name_main'    => $volume['name_file'],
            'workers'      => $workers,
            'footnotes'    => $footnotes,
            'images'       => $images
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

        // if (!empty($request->getReferrer())) {
        //     if (strrpos($request->getReferrer(), "ranobeclub")) {
        //         return $response->withRedirect("/r/" . array_shift(explode('/', $page)));
        //     }
        //     if (!strrpos($request->getReferrer(), "ruranobe.ru") &&
        //         !strrpos($request->getReferrer(), "vk.com") &&
        //         !strrpos($request->getReferrer(), "paveliarch.gnudip.de")
        //     ) {
        //         return $response->withRedirect("/r/" . $page);
        //     }
        // }
        // if ($request->getcookie('cid')) {
        //     $cid = ($request->getCookie('cid'));
        // } else {
        //     $cid = $this->uuid();
        //     $this->setCookie('cid', $cid);
        // }
        // $cid = urlencode($cid);
        // $ga = "http://www.google-analytics.com/collect?v=1&tid={$this->config['ga_tid']}&cid={$cid}&uip=" . urlencode($request->getIP()) . "&ua=" . urlencode($_SERVER['HTTP_USER_AGENT']);
        // if ($request->getReferrer())
        //     $ga .= "&dr=" . urlencode($request->getReferrer());
        // if ($wgUser->getId())
        //     $ga .= '&uid=' . $wgUser->getId();
        // file_get_contents($ga . "&t=pageview&dh=ruranobe.ru&dp=" . urlencode("/d/$format/$page"));
        // file_get_contents($ga . "&t=event&ec=download&ea=$format&el=" . urlencode($page));

        return $converter->convert($response);
        //return $response->withJson($pdb);
    }
);

// Run app
$app->run();