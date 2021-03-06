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
    protected $covers;
    protected $translators;
    protected $seriestitle;
    protected $seriesnum;
    protected $revcount;
    protected $isbn;
    protected $command;
    protected $workers;
    protected $touched;
    protected $nameurl;
    protected $namemain;
    protected $height;
    protected $footnotes;
    protected $images;
    protected $nocache;
    protected $colorImgs;

    public function __construct($height, $pdb, $text, $config, $colorImgs)
    {
        $this->height = $height;
        $this->colorImgs = $colorImgs;
        $this->parseMainReleasesRow($pdb);
        $this->text_to_convert = $text;
        $this->config          = $config;
    }

    private function apiCall($function, $params, $opts_var = null)
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
        $json_text = curl_exec($curl);
        curl_close($curl);
        $json = json_decode($json_text);
        if (isset($_GET['debug']) && (!is_object($json) || isset($json->error))) {
            var_dump(
                array(
                    'function'   => $function,
                    'params'     => $params,
                    'curl_opts'  => $opts,
                    'curl_error' => curl_error($curl),
                    'result'     => ($json ?: $json_text)
                )
            );
            echo "\n\n";
        }
        return $json;
    }

    /**
     * @param \Slim\Http\Response $response
     * @return \Slim\Http\Response
     */
    public function convert($response)
    {
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
        if (!$this->colorImgs) {
    	    $h = '_nocolors'.$h;
        }
        $part_filename = '/' . $this->nameurl . '/' . $this->escapedFileName() . $h . $this->getExt();
        $bin           = false;
        if (!$this->nocache) {
            $cache_filename = $this->config['folder'] . $part_filename;
            $ftp_filename   = '/srv/ftp/ruranobe.ru/d/' . $this->getExt() . $part_filename;
            $ftp_dirname    = dirname($ftp_filename);
            $json           = $this->apiCall('disk/resources', ['fields' => 'modified', 'path' => $cache_filename]);
            if (!isset($json->modified) || $this->touched > strtotime($json->modified)) {
                $bin = $this->convertImpl($this->text_to_convert);
                if (!is_dir($ftp_dirname)) {
                    mkdir($ftp_dirname, 0755, true);
                }
                file_put_contents($ftp_filename, $bin);
                $json = $this->apiCall('disk/resources', ['path' => dirname($cache_filename)], [CURLOPT_PUT => true]);
                if (isset($json->error) && $json->error == 'DiskPathDoesntExistsError') {
                    $this->apiCall(
                        'disk/resources',
                        ['path' => dirname(dirname($cache_filename))],
                        [CURLOPT_PUT => true]
                    );
                    $this->apiCall('disk/resources', ['path' => dirname($cache_filename)], [CURLOPT_PUT => true]);
                }
                $json = $this->apiCall('disk/resources/upload', ['overwrite' => 'true', 'path' => $cache_filename]);
                if (!empty($json->href)) {
                    $tmpfile = tempnam(sys_get_temp_dir(), 'cvcache');
                    file_put_contents($tmpfile, $bin);
                    $fp       = fopen($tmpfile, 'r');
                    $curl_upl = curl_init();
                    curl_setopt($curl_upl, CURLOPT_HTTPHEADER, ['Authorization: OAuth ' . $this->config['key']]);
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
                    trigger_error(
                        "Cannot upload convertor cache of file '$part_filename' - no upload link",
                        E_USER_WARNING
                    );
                }
            }
            $json = $this->apiCall(
                'disk/public/resources/download',
                ['path' => $part_filename, 'public_key' => $this->config['public_key']]
            );
            if (isset($json->href)) {
                return $response->withRedirect($json->href);
            }
        }
        if (!$bin) {
            $bin = $this->convertImpl($this->text_to_convert);
            error_log('converted ' . $part_filename . '. peak memory usage: ' . memory_get_peak_usage());
        }
        return $this->makeDownload($bin, $response);
    }

    protected function escapexml($text)
    {
        return htmlspecialchars($text, ENT_XML1, ini_get("default_charset"), false);
    }

    abstract protected function convertImpl($text);

    abstract protected function getMime();

    abstract protected function getExt();

    /**
     * @param                     $bin
     * @param \Slim\Http\Response $response
     * @return \Slim\Http\Response
     */
    protected function makeDownload($bin, $response)
    {
        $filename = $this->escapedFileName() . ($this->colorImgs == 0 ? '_nocolors' : '') . ($this->height == 0 ? '_nopic' : '') . $this->getExt();
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
//        return $response->withHeader("Pragma", "public")
//            ->withHeader("Last-modified", $lastmod)
//            ->withHeader("Expires", 0)
//            ->withHeader("Accept-Ranges","bytes")
//            ->withHeader("Connection", "close")
//            ->withHeader("Content-Type", $this->getMime())
//            ->withHeader("Content-Disposition", "attachment; filename=\"$filename\"")
//            ->withHeader("Content-Transfer-Encoding", "binary")
//            ->withHeader("Content-Length", strlen($bin))
//            ->write($bin);
    }

    private function escapedFileName()
    {
        $string = str_replace(' ', '_', $this->namemain);
        $string = preg_replace('/[#<\$\+%>!`&\*\'\|\{?"=\}\/:\\@]/', '', $string);
        return $string;
    }

    private function parseMainReleasesRow($pdb)
    {
        $this->annotation  = $pdb['annotation'];
        $this->author      = $pdb['author'];
        $this->nameru      = $pdb['name_ru'];
        $this->illustrator = $pdb['illustrator'];
        $this->covers      = $pdb['covers'];
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
        $this->images      = $pdb['images'];
        $this->nocache     = $pdb['nocache'];
    }
}