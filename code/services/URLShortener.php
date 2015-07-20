<?php
class URLShortener extends Object {
    public static function shorten($url) {
        $url = new CheckfrontShortenedURL(array(
            'URL' => $url
        ));
        $url->write();
        return $url->Key;
    }
}