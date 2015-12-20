<?php
namespace Ruranobe\Converters;

/**
 * Class Fb2Converter
 * Author: Samogot
 */
class Fb2Converter extends Converter
{
    protected function getMime()
    {
        return "application/x-fictionbook; charset=utf-8";
    }

    protected function getExt()
    {
        return ".fb2";
    }

    protected function convertImpl($text)
    {
        $descr['book_title'] = $this->nameru;
        foreach ([$this->author, $this->illustrator] as $aut) {
            if ($aut) {
                $descr['author'] = "";
                foreach (explode(',', $aut) as $au) {
                    $a = explode(' ', trim($au));
                    $descr['author'] .= "<author>";
                    if (isset($a[1])) {
                        $descr['author'] .= '<first-name>' . array_shift($a) . '</first-name>';
                        $descr['author'] .= '<last-name>' . array_pop($a) . '</last-name>';
                        if ($a) {
                            $descr['author'] .= '<middle-name>' . implode(' ', $a) . '</middle-name>';
                        }
                    } else {
                        $descr['author'] .= "<nickname>$a[0]</nickname>";
                    }
                    $descr['author'] .= "</author>";
                }
            }
        }

        $descr['annotation'] = "";
        if ($this->annotation) {
            $descr['annotation'] = "\n\n" . $this->annotation . "\n\n";
            $descr['annotation'] = preg_replace('@\'\'\'(.*?)\'\'\'@', "<b>\\1</b>", $descr['annotation']);
            $descr['annotation'] = preg_replace('@\'\'(.*?)\'\'@', "<i>\\1</i>", $descr['annotation']);
            $descr['annotation'] = preg_replace('/\n{3,}/', "\n\n<empty-line />\n\n", $descr['annotation']);
            $descr['annotation'] = preg_replace('/(?<=\n\n)(.*?)(?=\n\n)/s', "<p>\\1</p>", $descr['annotation']);
            $descr['annotation'] = preg_replace('/\n\n+/', "\n", $descr['annotation']);
            $descr['annotation'] = preg_replace('@<(/?)b>@', '<\1strong>', $descr['annotation']);
            $descr['annotation'] = preg_replace('@<(/?)i>@', '<\1emphasis>', $descr['annotation']);
            $descr['annotation'] = str_replace('<p><empty-line /></p>', '<empty-line />', $descr['annotation']);
            $descr['annotation'] = trim($descr['annotation']);
            $descr['annotation'] = "<annotation>$descr[annotation]</annotation>";
        }

        $images = [];
        if ($this->cover) {
            /*if(Title::makeTitle(NS_FILE,$this->cover.'.png')->exists())
            {*/
            //$cover = $this->cover . '.png';
            $cover = $this->cover;
            /*}
            else
            {
                $cover = $this->cover.'.jpg';
            }*/
            $descr['coverpage']   = "<coverpage><image l:href=\"#" . str_replace(' ', '_', $cover) . "\"/></coverpage>";
            $images[]             = $cover;
            $descr['coverpage_n'] = $cover;
        }

        $descr['translator'] = "";
        if ($this->translators) {
            foreach (explode(',', $this->translators) as $tr) {
                $descr['translator'] .= "<translator><nickname>$tr</nickname></translator>";
            }
        }

        if ($this->seriestitle) {
            $descr['sequence'] = "<sequence name=\"{$this->seriestitle}\"" . ($this->seriesnum ? " number=\"{$this->seriesnum}\"" : '') . " />";
        }

        $descr['date2'] = '<date value=' . date('"Y-m-d">j F Y, H:i', strtotime($this->touched)) . '</date>';
        //$descr['src_url']=Title::makeTitle(NS_MAIN,$this->nameurl)->getFullUrl('',false,PROTO_HTTP);
        $descr['id'] = 'RuRa_' . str_replace('/', '_', $this->nameurl);

        $descr['isbn'] = "";
        if ($this->isbn) {
            $descr['isbn'] = "<publish-info><isbn>{$this->isbn}</isbn></publish-info>";
        }

        if ($this->command == 'RuRa-team') {
            $credit = "<section>
					<title><p>Реквизиты переводчиков</p></title>
					<p>Над переводом работала команда <strong>RuRa-team</strong></p>\n";
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <strong>$wk[3]</strong></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>Для желающих отблагодарить переводчика материально имеются webmoney-кошельки команды:</p>
					<p><strong>R125820793397</strong></p>
					<p><strong>U911921912420</strong></p>
					<p><strong>Z608138208963</strong></p>
					<p>QIWI-кошелек:</p>
					<p><strong>+79116857099</strong></p>
					<p>Яндекс-деньги:</p>
					<p><strong>410012692832515</strong></p>
					<p>А так же счет для перевода с кредитных карт:</p>
					<p><strong>4890 4941 5384 9302</strong></p>
					<empty-line/>
					<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					<p><strong>Любое распространение перевода за пределами нашего сайта запрещено. Если вы скачали файл на другом сайте - вы поддержали воров</strong></p>
					<empty-line/>
					<empty-line/>
					<empty-line/>
					</section>';
        } elseif (strpos($this->command, 'RuRa-team') !== false) {
            $credit = "<section>
					<title><p>Реквизиты переводчиков</p></title>
					<p>Над релизом работали {$this->command}</p>\n";
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <strong>$wk[3]</strong></p>\n";
            }
            $credit .= '<p>Самый свежий перевод всегда можно найти на сайте нашего проекта:</p>
					<p><a l:href="http://ruranobe.ru">http://ruranobe.ru</a></p>
					<p>Чтобы оставаться в курсе всех новостей, вступайте в нашу группу в Контакте:</p>
					<p><a l:href="http://vk.com/ru.ranobe">http://vk.com/ru.ranobe</a></p>
					<empty-line/>
					<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<p><strong>Любое коммерческое использование данного текста или его фрагментов запрещено</strong></p>
					</section>';
        } else {
            $credit = "<section>\n<title><p>Реквизиты переводчиков</p></title>\n";
            if ($this->command) {
                $credit .= "<p>Перевод команды {$this->command}</p>\n";
            }
            foreach (explode('|-', trim($this->workers)) as $wk) {
                $wk = explode('|', trim($wk));
                $credit .= "<p>$wk[1]: <strong>$wk[3]</strong></p>\n";
            }
            $credit .= '<p>Версия от ' . date('d.m.Y', strtotime($this->touched)) . '</p>
					<empty-line/>
					<p><strong>Любое коммерческое использование данного текста или его фрагментов запрещено</strong></p>
					</section>';
        }

        $text = str_replace(
            '<span style="position: relative; text-indent: 0;"><span style="display: inline-block; font-style: normal">『　　　』</span><span style="position: absolute; font-size: .7em; top: -11px; left: 50%"><span style="position: relative; left: -50%;">',
            '[<sup>',
            $text
        );
        $text = str_replace('</span></span></span>', '</sup>]', $text);
        //$text = preg_replace('@『.*?』.{0,300}<span>([а-яА-Я]+?)</span>@s','[<sup>\\1</sup>]',$text);
        $text = preg_replace('@<span id="\w+" class="ancor"></span>\n@', '', $text);
        $text = preg_replace('@</?span.*?>@', '', $text);
        $text = preg_replace('@</?nowiki>@', '', $text);
        $text = preg_replace('@\'\'\'(.*?)\'\'\'@', "<b>\\1</b>", $text);
        $text = preg_replace('@\'\'(.*?)\'\'@', "<i>\\1</i>", $text);
        $text = preg_replace('@\s*(<center .*?>.*</center>)\s*@', "\n\n\n\n\\1\n\n\n\n", $text);
        $text = preg_replace('/\s*(==.*==|\[\[File:.*\]\])\s*/', "\n\n\\1\n\n", $text);
        $text = preg_replace('@(</center>|==|\]\])\n*(<center |==|\[\[File:)@', "\\1\n\n\\2", $text);
        $text = "\n\n" . trim($text) . "\n\n";
        $text = preg_replace('/\n{3,}/', "\n\n<empty-line />\n\n", $text);
        //$text=preg_replace('/(?<=\n\n)(.*?)(?=\n\n)/s',"<p>\\1</p>",$text);
        $text = preg_replace('/\n\n+/', "\n", $text);
        $text = preg_replace('@<(/?)b>@', '<\1strong>', $text);
        $text = preg_replace('@<(/?)i>@', '<\1emphasis>', $text);
        $text = str_replace('<p><empty-line /></p>', '<empty-line />', $text);
        $text = preg_replace('@(<p>)?<center .*?>(.*)</center>(</p>)?@', "<subtitle>\\2</subtitle>", $text);
        $text = preg_replace('@\[(https?://[^ ]*) ([^\]]*)\]@', '<a href="\1">\2</a>', $text);
        $text = preg_replace('@<p>\s*__TOC__\s*</p>@', '', $text);

        if ($this->height == 0) {
            $text = preg_replace('/(<p>)?\[\[File:.*\]\](<\/p>)?/u', '', $text);
        } else {
            $text = preg_replace_callback(
                '/(<p>)?\[\[File:(.*?)\|.*\]\](<\/p>)?/u',
                function ($match) use (&$images) {
                    $images[] = $match[2];
                    return "<image l:href=\"#" . str_replace(' ', '_', $match[2]) . "\"/>";
                },
                $text
            );
        }

        $text = preg_replace('/(<p>)?(==+.*==+)(<\/p>)?/', "\\2", $text);
        preg_match_all('/(^(={2,})[^\n]*\2$)(.{50})/ms', $text, $m);
        for ($i = 1; $i < count($m[0]); $i++) {
            $d = strlen($m[2][$i]) - strlen($m[2][$i - 1]);
            if ($d > 0) {
                for ($j = 0; $j < $d; $j++) {
                    $text = str_replace($m[0][$i - 1], $m[1][$i - 1] . "\n<section>" . $m[3][$i - 1], $text);
                }
            } elseif ($d < 0) {
                for ($j = 0; $j < (-$d); $j++) {
                    $text = str_replace($m[0][$i], "</section>\n" . $m[0][$i], $text);
                }
            }
        }
        $text = preg_replace('/==+(.*?)==+/', "</section>\n<section>\n<title><p>\\1</p></title>", $text);
        $text = "<section>\n<empty-line/>\n" . trim($text);
        for ($i = 0; $i < strlen($m[2][count($m[2]) - 1]) - 1; $i++) {
            $text .= "\n</section>";
        }
        if (count($m[2]) < 1) {
            $text .= "\n</section>";
        }
        $text = preg_replace('@<empty-line />(?!\s*<p>)\n*@', '', $text);
        $text = preg_replace('@(<title>.*?</title>)\s*(<section>)\s*(<image .*?/>)@', "\\1\n\\3\n\\2", $text);
        $text = preg_replace('@(?<!</p>\n)<empty-line />\n*@', '', $text);
        $text = preg_replace('@<section>\s*</section>\n*@', '', $text);
        $text = preg_replace('@</title>\s*</section>@', "</title>\n<empty-line />\n</section>", $text);

        $notes = "";
        if (preg_match_all('@<ref>(.*?)</ref>@s', $text, $m)) {
            $notes = "<body name=\"notes\">\n\t<title><p>Примечания</p></title>\n";
            for ($i = 1; $i <= count($m[0]); $i++) {
                $text = str_replace($m[0][$i - 1], "<a l:href=\"#note$i\" type=\"note\">[$i]</a>", $text);
                $notes .= "\t<section id=\"note$i\">\n\t\t<title><p>$i</p></title>\n\t\t<p>" . $m[1][$i - 1] . "</p>\n\t</section>\n";
            }
            $notes .= '</body>';
        }

        $binary = "";
        /*if($images)
        {
            $images = RepoGroup::singleton()->getLocalRepo()->findFiles($images);
            foreach($images as $imname=>$file) {
                $image_url=$_SERVER['DOCUMENT_ROOT'].$file->transform(array('width'=>$this->height*2+300,'height'=>$this->height?$this->height:null))->getUrl();
                $binary.='<binary id="'.str_replace(' ','_',$imname).'" content-type="'.$file->getMimeType().'">'."\n".base64_encode(file_get_contents($image_url))."\n</binary>";
            }
        }*/
        $text = trim($text);

        $fb2 = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
	<FictionBook xmlns=\"http://www.gribuser.ru/xml/fictionbook/2.0\" xmlns:l=\"http://www.w3.org/1999/xlink\">
		<description>
			<title-info>
				<genre>sf</genre>
				$descr[author]
				<book-title>$descr[book_title]</book-title>
				$descr[annotation]
				$descr[coverpage]
				<lang>ru</lang>
				<src-lang>jp</src-lang>
				$descr[translator]
				$descr[sequence]
			</title-info>
			<document-info>
				<author>
					<nickname>Samogot</nickname>
					<home-page>http://ruranobe.ru/r/User:Samogot</home-page>
					<email>convert_fb2@ruranobe.ru</email>
				</author>
				<program-used>Samogot`s wiki to fb2 converter</program-used>
				$descr[date2]
				<src-url>$descr[src_url]</src-url>
				<id>$descr[id]</id>
				<version>$descr[version]</version>
			</document-info>
			$descr[isbn]
		</description>
		<body>
		<title><p>$descr[book_title]</p></title>
	$credit
	$text
		</body>
	$notes
	$binary
	</FictionBook>";

        // $old=error_reporting( -1 );
        // ob_start();
        // $dom = new DOMDocument();
        // $dom->loadXML($fb2);
        // $valid=$dom->schemaValidate('FictionBook.xsd');
        // $err=ob_get_clean();
        // error_reporting( $old );
        // if(!$valid){
        // $message = "Возникла ошибка при конвертации $descr[src_url] в формат fb2 с размером изображений $this->height\nПодробности:\n\n$err\n";
        // $message = wordwrap($message, 70);
        // mail('convert_fb2@ruranobe.ru', "Fb2 Validation Error: $descr[src_url]", $message);
        // }

        return $fb2;
    }
}