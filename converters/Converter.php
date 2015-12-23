<?php

namespace Ruranobe\Converters;

/**
 * Class RuraConverter
 * Author: Keiko
 * Defines abstract class for all Converters.
 */
abstract class Converter
{
    protected $config;
    protected $text_to_convert;

    /* These parameters should be incapsulated into a class */
    protected $annotation;
    protected $author;
    protected $nameru;
    protected $illustrator;
    protected $cover;
    protected $translators;
    protected $seriestitle;
    protected $seriestnum;
    protected $revcount;
    protected $isbn;
    protected $command;
    protected $workers;
    protected $touched;
    protected $nameurl;
    protected $namemain;
    protected $height;
    protected $footnotes;

    public function __construct($height, $pdb, $text, $config)
    {
        $this->height = $height;
        $this->parseMainReleasesRow($pdb);
        $this->text_to_convert = $text;
        $this->config = $config;
    }

    private function apiСall($function, $params, $opts_var = null)
    {
        if (!isset($opts_var) || !$opts_var) {
            $opts_var = [];
        }
        $opts_base = [
            CURLOPT_HTTPHEADER     => ['Authorization: OAuth ' . $this->config['key']],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_URL            => 'https://cloud-api.yandex.net/v1/' . $function . '?' . http_build_query($params),
        ];
        $opts      = $opts_var + $opts_base;
        $curl      = curl_init();
        curl_setopt_array($curl, $opts);
        $json = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($json);
        if (isset($_GET['debug']) && (!is_object($json) || isset($json->error))) {
            var_dump($function, $params, $opts, http_build_query($params), $json);
        }
        return $json;
    }

    public function convert()
    {
        if (isset($_GET['debug'])) {
            echo '<xmp>';
        }
        /*else {
            echo "Sory, download temporary disabled";
            exit;
        }*/
        switch ($this->height) {
            case 0:
                $h = '_nopic';
                break;
            case 1080:
                $h = '';
                break;
            default:
                $h = '_' . $this->height;
        }
        /*$part_filename  = '/' . $this->nameurl . '/' . str_replace(' ', '_', $this->namemain) . $h . $this->getExt();
        $cache_filename = 'ConvertorsCache' . $part_filename;

        $json = $this->apiСall('disk/resources', ['fields' => 'modified', 'path' => $cache_filename]);

        if (!isset($json->modified) || strtotime($this->touched) > strtotime($json->modified)) {
            $bin  = $this->convertImpl($this->text_to_convert);
            $json = $this->apiСall('disk/resources', ['path' => dirname($cache_filename)], [CURLOPT_PUT => true]);

            if (isset($json->error) && $json->error == 'DiskPathDoesntExistsError') {
                $this->apiСall('disk/resources', ['path' => dirname(dirname($cache_filename))], [CURLOPT_PUT => true]);
                $this->apiСall('disk/resources', ['path' => dirname($cache_filename)], [CURLOPT_PUT => true]);
            }
            $json = $this->apiСall('disk/resources/upload', ['overwrite' => 'true', 'path' => $cache_filename]);

            if (!empty($json->href)) {
                $tmpfile = tempnam(sys_get_temp_dir(), 'cvcache');
                file_put_contents($tmpfile, $bin);
                $fp       = fopen($tmpfile, 'r');
                $curl_upl = curl_init();
                curl_setopt($curl_upl, CURLOPT_HTTPHEADER, ['Authorization: OAuth '. $this->config['key']]);
                curl_setopt($curl_upl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl_upl, CURLOPT_URL, $json->href);
                curl_setopt($curl_upl, CURLOPT_INFILE, $fp);
                curl_setopt($curl_upl, CURLOPT_INFILESIZE, filesize($tmpfile));
                curl_setopt($curl_upl, CURLOPT_PUT, true);
                curl_setopt($curl_upl, CURLOPT_UPLOAD, true);
                curl_exec($curl_upl);
                curl_close($curl_upl);
                fclose($fp);
                unlink($tmpfile);
            } else {
                trigger_error("Cannot upload convertor cache - no upload link", E_USER_WARNING);
            }
        }
        $json = $this->apiСall(
            'disk/public/resources/download',
            ['path' => $part_filename, 'public_key' => $this->config['public_key']]
        );
        if (isset($json->href)) {
            header('Location: ' . $json->href);
        } else {*/
        //if (!$bin) {
        $bin = $this->convertImpl($this->text_to_convert);
        //}
        $this->makeDownload($bin);
        //}
    }

    abstract protected function convertImpl($text);

    abstract protected function getMime();

    abstract protected function getExt();

    protected function makeDownload($bin)
    {
        $filename = str_replace(' ', '_', $this->namemain) . ($this->height == 0 ? '_nopic' : '') . $this->getExt();
        $lastmod  = date(DATE_RFC2822, $this->touched);
        header('Pragma: public');
        header("Last-modified: $lastmod");
        header("Expires: 0");
        header("Accept-Ranges: bytes");
        header("Connection: close");
        header('Content-Type: ' . $this->getMime());
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Content-Transfer-Encoding: binary");
        header('Content-Length: ' . strlen($bin));
        flush();
        print $bin;
    }

    private function parseMainReleasesRow($pdb)
    {
        $this->annotation  = $pdb['annotation'];
        $this->author      = $pdb['author'];
        $this->nameru      = $pdb['name_ru'];
        $this->illustrator = $pdb['illustrator'];
        $this->cover       = $pdb['cover'];
        $this->translators = $pdb['translators'];
        $this->seriestitle = $pdb['series_title'];
        $this->seriesnum   = $pdb['series_num'];
        $this->revcount    = $pdb['revcount'];
        $this->isbn        = $pdb['isbn'];
        $this->command     = $pdb['command'];
        $this->touched     = $pdb['touched'];
        $this->nameurl     = $pdb['name_url'];
        $this->namemain    = $pdb['name_main'];
        $this->workers     = $pdb['workers'];
        $this->footnotes   = $pdb['footnotes'];
    }
}