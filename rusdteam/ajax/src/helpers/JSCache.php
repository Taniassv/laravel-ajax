<?php 
namespace Rusdteam\Dom\Helpers;

use \Illuminate\Support\Facades\Cache;

class JSCache {

    public $cacheName;
    public $cacheAction;
    
    public $enable = false;

	//Вывод закэшированной информации
    private function printCacheResult($key = NULL) {
        $cacheKey = $key ?: $this->getCacheKey();
        $cacheContent = Cache::get($cacheKey);

        if($cacheContent) {
            return $cacheContent;
        } else {
            return false;
        }
    }

    //Получения ключа кэша для данного контента
    private function getCacheKey() {
        $key = 'content_' . $this->cacheName . $this->cacheAction;
        return $key;
    }

    public function setCacheAction($value) {
        $this->cacheAction = $value;
        return $this;
    }

    public function getCacheAction() {
        return $this->cacheAction;
    }

    public function setCacheName($newCacheName) {
        $this->cacheName = $newCacheName;
        return $this;
    }

    public function getCacheName() {
        return $this->cacheName;
    }

    //Добавление результата в кэш
    private function cacheResult(Response $response) {
        if(!empty($this->cacheAction) && !empty($this->cacheName)) {
            $content = $response->getContent();
            $keyCache = $this->getCacheKey();

            Cache::add($keyCache, $content);
        }
    }
}