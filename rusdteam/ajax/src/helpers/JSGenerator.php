<?php
namespace Rusdteam\Dom\Helpers;

class JSGenerator
{
    public $json;
    private $_id;
    public $responses;
    
    private $objectDomClass;

    public function __call($method, $argc)
    {
        if(!$this->objectDomClass) {
            $this->objectDomClass = \App::make('Ajax');
        }
        
        $json = call_user_func_array([$this->objectDomClass, $method], $argc)->execute(true);
        $array = json_decode($json, true);

        $this->responses = array_merge_recursive( (array) $this->responses, $array );
    }

    public function createElement($element, $option = [])
    {
        $thisElement['tagName'] = $element;

        $this->_id = $element;

        if (isset($option['more']['setElement'])) {
            $thisElement['setElement'] = $option['more']['setElement'];
        }

        if (isset($option['more']['value'])) {
            $thisElement['content'] = $option['more']['value'];
        }

        unset($option['more']);

        $thisElement['attributes'] = $option;

        $this->responses['elements'][] = json_encode($thisElement);
        $this->addCallback('createElementCall');

        return $this;
    }

    public function css($option, $value)
    {
        $this->addOptionArray(['selectorCSS' => $this->_id, 'option' => $option, 'value' =>
            $value, ]);

        $this->addCallback('changeCss');

        return $this;
    }

    public function cssArray(array $options)
    {
        $json = json_encode($options);

        $this->addOptionArray(['selectorCSS' => $this->_id, 'json' => $json]);

        $this->addCallback('changeCssArray');

        return $this;
    }

    public function animate($option, $value, $time, callable $callback = null)
    {

        if (is_string($callback)) {
            $cl = $callback;
        } else {
            if ($callback) {
                $cl = $callback(new Ajax())->execute(true);
            } else {
                $cl = json_encode([]);
            }
        }

        $this->addOptionArray(['selectorAnimate' => $this->_id, 'optionAnimate' => $option,
            'valueAnimate' => $value, 'callbackAnimate' => $cl, 'timeAnimate' => $time]);

        $this->addCallback('animate');

        return $this;
    }

    public function animateArray()
    {

    }

    public function setTimeOut(Callable $function, $timeOut)
    {
        $json = $function(\App::make('Ajax'))->execute(true);
        $this->addCallback('TimeOut');
        $this->addOption('jsonForTimeOut', $json);
        $this->addOption('secondForTimeOut', $timeOut);

        return $this;
    }
    
    public function __destruct() {
        $this->objectDomClass = NULL;
    }
}
