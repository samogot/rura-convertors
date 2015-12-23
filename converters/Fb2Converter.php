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

        $descr['annotation'] = '<annotation>'.$this->annotation.'</annotation>';

        $images = [];
        if ($this->cover) {
            $cover = $this->cover;
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

        if($this->height==0) 
			{
				$text=preg_replace('/(<p[^>]*>)?<img[^>]*>(<\/p>)?/u','',$text);

			}
			else
                        {
                            $text=preg_replace_callback(
							               '/<img[^>]*src="([^"]*)"[^>]*>/u', 
											function ($match) use(&$images) 
											{
											   $images[]=$match[1];
											   return "<image l:href=\"#".str_replace(' ','_',$match[1])."\"/>";
											}, 
											$text);

             }


        $j = 0;
	$notes = '';
        $isnotes = false;
        $footnotes = explode(',;,', $this->footnotes);
        for($i = 0; $i < sizeof($footnotes); $i++)
        {
           if (is_numeric($footnotes[$i]))
           {
              $j = 0;
              if ($isnotes == false)
              {
                 $notes .= "<body name=\"notes\">\n\t<title><p>Примечания</p></title>\n";
              }
              $isnotes = true;
              $notes .= "\t<section id=\"cite_note-$footnotes[$i]\">\n\t\t<title><p>$j</p></title>\n\t\t<p>" . $footnotes[$i+1] . "</p>\n\t</section>\n";
              $i++;
              $j++;
           }
        } 
        if ($isnotes == true)
        {
           $notes .= '</body>';
        }

			$binary="";
			if($images)
			{
				foreach($images as $index => $image) 
				{		
					if (file_get_contents($image,0,null,0,1)) 
					{					

						$fileContents = file_get_contents($image);
						$finfo = new \finfo(FILEINFO_MIME);
						$mimeType = $finfo->buffer($fileContents);
//$image_url=$_SERVER['DOCUMENT_ROOT'].$file->transform(array('width'=>$this->height*2+300,'height'=>$this->height?$this->height:null))->getUrl();
						$binary.='<binary id="'.$image.'" content-type="'.$mimeType.'">'."\n".base64_encode($fileContents)."\n</binary>";
					}
				}
			}

//        $text = trim($text);


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