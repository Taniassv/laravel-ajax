<?php
namespace Rusdteam\Dom\Helpers;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class Ajax {
	public $responses;
    public $time_start;
    public $time_close;
    public $time_limit = 2;
    public $divs;
    public $cacheName;
    public $cacheAction;

    private $options = array(
        'append'
    );

    private $closeApplication = FALSE;
    private $jsonContent;
    private $memoryStart;
    private $memoryClose;
    private $maxMemoryLimit = 3600;

    public $cache; //Object class
    public $generator; //Object class
    
    public function add(JSGenerator $element) {
        $this->responses = array_merge_recursive( (array) $this->responses, $element->responses );
    }
    
    public function __construct(JSGenerator $JSG, JSCache $JSC)
    {
        $this->generator = $JSG;
        $this->cache = $JSC;

        $this->memoryStart = memory_get_usage();
    }

    /*
        Добавить элементу аттрибут
    */
    /**
     * Ajax::setAttr()
     * 
     * @param mixed $element
     * @param mixed $attr
     * @param mixed $value
     * @return
     */
    public function setAttr($element, $attr, $value) {
        $this->addCallback('set_attribute');

        $this->addOptionArray([
            'element'               => $element,
            'elementAttribute'      => $attr,
            'elementAttributeValue' => $value
        ]);

        return $this;
    }
    
    public function callDom($element, $method, Callable $function) {
        $this->responses['callOfDom']['selector'] = $element;
        $this->responses['callOfDom']['methodName'] = $method;
        $this->responses['callOfDom']['json'] = json_encode(
                                                    $function( App::make('Ajax') )->execute(TRUE)
                                                );
        
        
        return $this;
    }
	
    public function note($msg) {
        $this->response($msg);
        
        return $this;
    }
    
    public function noteHTML($class, $title, $text) {
        return \View::make('basic/_note', [
            'class' => $class,
            'title' => $title,
            'text'  => $text
        ])->render();
    }
    
	/**
	 * Ajax::createElement()
	 * 
	 * @param mixed $element
	 * @param mixed $option
	 * @return object
	 */
	public function createElement($element, array $option) {
		$thisElement['tagName']  = $element;
		
		if(isset($option['more']['setElement']))
        {
            $thisElement['setElement'] = $option['more']['setElement'];
        } 
        
		if(isset($option['more']['value']))
        {
            $thisElement['content'] = $option['more']['value'];
        } 
		
		unset($option['more']);
		
		$thisElement['attributes'] = $option;
		$this->responses['elements'][] = json_encode($thisElement);
		$this->addCallback('createElementCall');
		
		return $this;
	}
	
	/**
	 * Ajax::setCss()
	 * 
	 * @param mixed $selector
	 * @param mixed $option
	 * @param mixed $value
	 * @return
	 */
	public function setCss($selector, $option, $value) {
		$this->addOptionArray([
			'selector' => $selector,
			'option'   => $option,
			'value'    => $value,
		]);
		
		$this->addCallback('changeCss');
		
		return $this;
	}
	
	/**
	 * Ajax::setCssArray()
	 * 
	 * @param mixed $selector
	 * @param mixed $options
	 * @return
	 */
	public function setCssArray($selector, array $options) {
		$json = json_encode($options);
		
		$this->addOptionArray([
			'selector' => $selector,
			'json'     => $json
		]);
		
		$this->addCallback('changeCssArray');
		
		return $this;
	}
	
	/**
	 * Ajax::animate()
	 * 
	 * @param mixed $selector
	 * @param mixed $option
	 * @param mixed $value
	 * @param mixed $time
	 * @param mixed $callback
	 * @return
	 */
	public function animate($selector, $option, $value, $time, callable $callback = NULL) {
		
		if(is_string($callback)) {
			$cl = $callback;
		} else {
			if($callback) {
                $object = \App::make('Ajax');
                $linkObject =& $object;

				$cl = $callback($linkObject);
                $linkObject->execute(TRUE);
			} else {
				$cl = json_encode([]);
			}
		}
		
		$this->addOptionArray([
			'selectorAnimate' => $selector,
			'optionAnimate'   => $option,
			'valueAnimate'    => $value,
			'callbackAnimate' => $cl,
			'timeAnimate'     => $time
		]);
		
		$this->addCallback('animate');
		
		return $this;
	}
	
	/**
	 * Ajax::setTimeOut()
	 * 
	 * @param mixed $function
	 * @param mixed $timeOut
	 * @return
	 */
	public function setTimeOut(Callable $function, $timeOut) {
        $object = \App::make('Ajax');
        $linkObject =& $object;

		$function($linkObject);
        $json = $object->execute(TRUE);

        $this->addCallback('TimeOut');
		$this->addOption('jsonForTimeOut', $json);
		$this->addOption('secondForTimeOut', $timeOut);
		
		return $this;
	}

    /*
     * Вызвать заранее определенную JS функцию в async.js для добавления сообщения в консоль
     */
    /**
     * Ajax::addConsole()
     * 
     * @param mixed $msg
     * @return
     */
    public function addConsole($msg) {
        $this->addCallback('console_add');
        $this->addOption('msgForConsole', $msg);

        return $this;
    }

    /*
     * Добавить опцию в запрос для получения их значений в функции JS
     */
    /**
     * Ajax::addOption()
     * 
     * @param mixed $name
     * @param mixed $value
     * @return
     */
    public function addOption($name, $value) {
        if ($name && $value) {
            $this->responses['option'][$name] = $value;
        }

        return $this;
    }

    /*
     * Добавить массив опций в запрос для получения их значений в функции JS
     */
    /**
     * Ajax::addOptionArray()
     * 
     * @param mixed $options
     * @return
     */
    public function addOptionArray(array $options) {
        foreach($options as $name => $value) {
            $this->addOption($name, $value);
        }

        return $this;
    }



    /*
     * Добавить ajax запрос (используется для JSBuilder)
     */
    /**
     * Ajax::addAjaxRequest()
     * 
     * @param mixed $url
     * @return
     */
    public function addAjaxRequest($url) {
        $this->addOption('ajaxRequestUrl', $url);
        $this->addCallback('ajaxRequestUrl');
        return $this;
    }

    /*
     * Воспользоваться функцией JavaScript, которая должна принимать 1 аргументом - json ответ от сервера
     */
    /**
     * Ajax::addCallback()
     * 
     * @param mixed $functionName
     * @param bool $is_array
     * @return
     */
    public function addCallback($functionName, $is_array = false) {
        if(isset($this->responses['callback']) && !is_array($this->responses['callback'])) {
			$callback = $this->responses['callback'];
			$this->responses['callback'] = [];
			$this->responses['callback'][] = $callback;
			$this->responses['callback'][] = $functionName;
		} elseif(isset($this->responses['callback']) && is_array($this->responses['callback'])) {
			$this->responses['callback'][] = $functionName;
		} else {
			$this->responses['callback'] = $functionName;
		}
		
		$this->responses['callback'] = is_array($this->responses['callback']) ? array_unique($this->responses['callback']) : $this->responses['callback'];
		
        return $this;
    }

    /*
    * Дебаг ошибок
    */
    /**
     * Ajax::debugError()
     * 
     * @param mixed $error
     * @return
     */
    public function debugError($error) {
        $this->addConsole('DebugError:'.$error);
        return $this;
    }


    /*
     * Добавить контент для нового дива
     * $name - селектор элемента; Example: '.container', 'body', '#user_1'
     * $content - новое значение данного элемента
     */
    /**
     * Ajax::addDiv()
     * 
     * @param mixed $name
     * @param mixed $content
     * @return
     */
    public function setHTML($name, $content) {
        $this->divs[$name] = $content;
        return $this;
    }

    /*
     * Вывод ошибки и завершение приложения
     * $callback - анонимная функция, исполняемая до завершения приложения и вывода сообщения ошибки пользователю
     */
    /**
     * Ajax::error()
     * 
     * @param mixed $error
     * @param mixed $callback
     * @return
     */
    public function error($error, Callable $callback = NULL) {
        $array = ['errors' => $error, 'time' => time()];

        if($callback) {
            $json = $callback(\App::make('Ajax'))->execute(true);
        }
        
        $array = json_decode($json, true);
    
        $this->responses = $array;
        $this->responses['errors'] = $error;
        
        
        
        $this->execute();
        exit();
    }

    /*
     * Вызов функции JS для редиректа, заранее определенной в async.js
     */
    /**
     * Ajax::redirectUri()
     * 
     * @param mixed $uri
     * @return
     */
    public function redirectUri($uri) {
        $this->addCallback('redirect_uri');
        $this->addOption('uri', $uri);

        return $this;
    }
	
	/**
	 * Ajax::title()
	 * 
	 * @param mixed $new_title
	 * @return
	 */
	public function title($new_title) {
		$this->addOption('new_title', $new_title);
		$this->addCallback('changeTitle');
		
		return $this;
	}

    /*
     * Добавить вид к ответу
     * Возвращает HTML код шаблона
    */
    /**
     * Ajax::view()
     * 
     * @param mixed $template
     * @param mixed $data
     * @return
     */
    public function addView($template, array $data = array()) {
        return \Illuminate\Support\Facades\View::make($template, $data)->render();
    }

    /* Вызвать Confirm на JavaScript и определить функции
     * $callBackNameTrue  - имя функции JS, которая выполнится, если пользователь нажал "ОК"
     * $callBackNameFalse - имя функции JS, которая выполнится, если пользователь нажал "Отмена"
     */
    /**
     * Ajax::confirm()
     * 
     * @param mixed $msg
     * @param mixed $callBackNameTrue
     * @param mixed $callBackNameFalse
     * @return
     */
    public function confirm($msg, $callBackNameTrue, $callBackNameFalse = NULL) {
        $this->addCallback('openConfirm');
        $this->addOption('msgForConfirm', $msg);

        if(is_callable($callBackNameFalse)) {

            $objectFalse = \App::make('Ajax');
            $linkObjectFalse =& $objectFalse;
            $callBackNameFalse($linkObjectFalse);

            $jsonTrue = $linkObjectFalse->execute(TRUE);

            $this->addOptionArray([
                'jsonConfirmFalse' => $jsonTrue,
                'statusConfirmFalse' => 'JsonData'
            ]);

        } else {
            $this->addOption('functionNameForConfirmFalse', $callBackNameFalse);
        }

        if(is_callable($callBackNameTrue)) {
            $objectTrue = \App::make('Ajax');
            $linkObjectTrue =& $objectTrue;
            $callBackNameFalse($linkObjectTrue);

            $jsonTrue = $linkObjectTrue->execute(TRUE);

            $this->addOptionArray([
                'jsonConfirmTrue' => $jsonTrue,
                'statusConfirmTrue' => 'JsonData'
            ]);
        } elseif(!empty($callBackNameTrue)) {
            $this->addOption('functionNameForConfirmTrue', $callBackNameTrue);
        }

        return $this;
    }

    /*private function callback($callBack, $name) {
        if(is_callable($callBack)) {
            $jsonFalse = $callBack(new Ajax())->execute(TRUE);
            $this->addOption('json' . ucfirst($name), $jsonFalse);
        } else {
            $this->addOption('functionNameFor' . ucfirst($name), $callBack);
        }
    }*/

    //Добавить уведомление
    /**
     * Ajax::response()
     * 
     * @param mixed $msg
     * @return
     */
    public function response($msg) {
        $this->responses['message'] = $msg;
        return $this;
    }
	
	

    //Вывод на страницу JSON данных
	/**
	 * Ajax::execute()
	 * 
	 * @param bool $return
	 * @return
	 */
	public function execute($return = FALSE) {
        foreach ($this->options as $k => $option) {
            $this->responses['option'][$k] = $option;
        }

        $this->responses['content'] = $this->divs;

        $this->responses = $this->validJson($this->responses);
        
        $this->jsonContent = json_encode($this->responses);

        if($return) {
            return $this->jsonContent;
        }

        $response = new JsonResponse();
        $response->setData($this->responses);
        $response->send();

        $this->memoryClose = memory_get_usage();

        die(); //Никаких дополнительных ответов
    }


    /**
     * Ajax::validJson()
     * 
     * @param mixed $json
     * @return
     */
    private function validJson($json) {
        $array = [];
        foreach ($json as $k => $v) {
            $array[is_null($k) ? count($array) : $k] = is_null($v) ? '' : $v;
        }
        return $array;
    }
    

}