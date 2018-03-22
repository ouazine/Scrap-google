 <?php
 //$keyword : Le mot utilisé pour scraper
 //$nbserp & $ndd : nombre de page de resultat et  data centere google utilisé (.fr, .com, .ca...)
 function scraping2($ndd, $nbserp = 3, $keywords)
    {
		//Partie qui concerne les proxies (utilisation d'une ip differente a chaque fois)
        $timeout = 10;
        $proxies = array();
        $proxy = $this->getDoctrine()
            ->getRepository('BacklinkBundle:Proxy')
            ->findall();
        if ($proxy) {
            $indice = 0;
            foreach ($proxy as $prox) {
                $proxies[$indice] = $prox->getAdress() . ":" . $prox->getPort() . ":" .$prox->getLoginpwd();

                $indice++;
            }
        }
        // Choix de data center
        switch ($ndd) {
            case "fr":
                $ext = "fr";
                $hl = "fr";
                break;
            case "com":
                $ext = "com";
                $hl = "en";
                break;
            case "es":
                $ext = "es";
                $hl = "es";
                break;

            case "net":
                $ext = "net";
                $hl = "fr";
                break;
            case "ru":
                $ext = "ru";
                $hl = "en";
                break;
            default :
                $ext = "fr";
                $hl = "fr";

        }
        $nbserp = strip_tags($nbserp) - 1;
        $q = strip_tags($keywords);
        if (isset($proxies)) {
            $proxy_host = $proxies[array_rand($proxies)];
        }
        $px = explode(":", $proxy_host);
$proxy_ad= $px[0].":".$px[1];

        $proxy_ident = $px[2].":".$px[3];
        $page = 0;
        $urlgoogle = "http://www.google." . $ext . "/search?hl=" . $hl . "&q=" . urlencode($q) . "&start=" . $page . "&filter=0";
        $useragent = "Mozilla/5.0";
        $urlserp = "";
		
		//Utilisation de CURL pour le scrap.
        while ($page <= $nbserp) {
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_USERAGENT, $useragent);
                curl_setopt($ch, CURLOPT_URL, $urlgoogle);
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

                curl_setopt($ch, CURLOPT_FRESH_CONNECT, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

                if (preg_match('`^https://`i', $urlgoogle)) {
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                }

                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

                curl_setopt($ch, CURLOPT_HTTPPROXYTUNNEL, true);
                curl_setopt($ch, CURLOPT_PROXY, $proxy_ad);

                if ($proxy_ident)
                    curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxy_ident);
                $serps = curl_exec($ch);


                curl_close($ch);
            } else {
                $serps = file_get_contents($urlgoogle);
            }
            preg_match_all('/<h3 class="r"><a href="(.*?)"/si', $serps, $matches);
            $result = count($matches[1]);
            $page++;
            $urlgoogle = "http://www.google." . $ext . "/search?hl=" . $hl . "&q=" . urlencode($q) . "&start=" . $page . "0&filter=0";
            $i = 0;
            while ($i < $result) {
                $urlserp .= trim($matches[1][$i]);
                $urlserp = str_replace("/url?q=", "", $urlserp);
                $urlserp = preg_replace("~(.+&amp;sa)[^/]*~", "$1", $urlserp);
                $urlserp = str_replace("&amp;sa", "<br />", $urlserp);
                $urlserp = str_replace("/search?q=" . urlencode($q) . "&amp;tbm=plcs", "", $urlserp);
                $i++;
                flush();
            }
        }
        if ((isset($ndd)) && (isset($nbserp)) && (isset($keywords))) {
            if (($ndd) != '' && ($nbserp) != '' && ($keywords) != '') {


                $Resultscrap = urldecode($urlserp);
                return $Resultscrap;
            } else {
                return "error";
            }
        }
    }
	?>