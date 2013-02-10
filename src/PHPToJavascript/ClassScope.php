<?php

namespace PHPToJavascript;

class ClassScope extends CodeScope{

	var		$methodsStartIndex = FALSE;

	var 	$publicVariables = array();
	var		$staticVariables = array();

	var 	$parentClasses = array();


	var 	$currentVariableForConcattingValue = NULL;

	function addParent($value){
		$this->parentClasses[] = $value;
	}


	function getType(){
		return CODE_SCOPE_CLASS;
	}

	function  getEndOfScopeJS(){
		$js = "";
		$js .= "\n";
		$js .= $this->getClassInheritanceJS();
		$js .= "\n";
		$js .= $this->getClassVariableInitJS();
		return $js;
	}

	function getClassInheritanceJS(){
		$js = "";

		if(count($this->parentClasses) > 0){
			$js .= "\n\n";

			foreach($this->parentClasses as $parentClass){
				$childClass = $this->name;
				//Inheritance pattern taken from
				//https://developer.mozilla.org/en-US/docs/JavaScript/Introduction_to_Object-Oriented_JavaScript

				$js .= "// inherit $parentClass\n";
				$js .= "$childClass.prototype = new $parentClass();\n";

				$js .= "// correct the constructor pointer because it points to $parentClass\n";
				$js .= "$childClass.prototype.constructor = $childClass;\n";
			}

			$js .= "\n";
		}

		return $js;
	}


	function	getClassVariableInitJS(){

		//$parentScopeName = $this->parentScope->name;

		$js = "";

		foreach($this->publicVariables as $name => $value){
			if($value === FALSE){
				$value = 'null';
			}

			$js .= $this->name.".prototype.".$name." = $value;\n";
		}

		foreach($this->staticVariables as $name => $value){
			if($value === FALSE){
				$value = 'null';
			}

			$js .= $this->name.".".$name." = $value;\n";
		}

		return $js;
	}

	function	getJS(){

		$jsRaw = "//Start class here \n";

		$jsRaw .= $this->getJSRaw();
		$constructor = FALSE;

		foreach($this->jsElements as $jsElement){
			if($jsElement instanceof CodeScope){
				if($jsElement->getName() == '__construct'){
					$constructor = $jsElement->getJSRaw();
					break;
				}
			}
		}

		$parentConstructor = "";

		foreach($this->parentClasses as $parentClass){
			$parentConstructor .= "".$parentClass.".call(this);\n";
		}

		if($constructor !== FALSE){
			$constructorInfo = trimConstructor($constructor);
			$constructorInfo['body'] = $parentConstructor.$constructorInfo['body'];
			$jsRaw = str_replace(CONSTRUCTOR_PARAMETERS_POSITION, $constructorInfo['parameters'], $jsRaw);
			$jsRaw = str_replace(CONSTRUCTOR_POSITION_MARKER, $constructorInfo['body'], $jsRaw);
		}
		else{
			//There is no constructor - just remove the magic strings
			$jsRaw = str_replace(CONSTRUCTOR_PARAMETERS_POSITION, '', $jsRaw);
			$jsRaw = str_replace(CONSTRUCTOR_POSITION_MARKER, $parentConstructor, $jsRaw);
		}

		return $jsRaw;
	}

	function	markMethodsStart(){
		if($this->methodsStartIndex === FALSE){
			$this->methodsStartIndex = count($this->jsElements);
			$this->addJS(CONSTRUCTOR_POSITION_MARKER);
		}
	}

	function	getScopedVariableForScope($variableName, $isClassVariable){
		$cVar = cvar($variableName);

		if(array_key_exists($cVar, $this->scopedVariables) == TRUE){
			$variableFlag = $this->scopedVariables[$cVar];

			if($isClassVariable == TRUE){
				if($variableFlag & DECLARATION_TYPE_PRIVATE){
					return 	$variableName;
				}
				if($variableFlag & DECLARATION_TYPE_STATIC){
					return 	$variableName;
				}
				if($variableFlag & DECLARATION_TYPE_PUBLIC){
					return 	'this.'.$variableName;
				}
			}
		}

		if($isClassVariable == TRUE){
			//Either a function or property set below where it is defined.
			// OR it could be a variable that is defined in the parent class' scope.
			return 	'this.'.$variableName;
		}

		return NULL;
	}

	function addStaticVariable($variableName){
		$this->staticVariables[$variableName] = FALSE;
		$this->currentVariableForConcattingValue = &$this->staticVariables[$variableName];
	}

	function addPublicVariable($variableName){
		$this->publicVariables[$variableName] = FALSE;
		$this->currentVariableForConcattingValue = &$this->publicVariables[$variableName];
	}

//	function	getDelayedJS($parentScopeName){
//		$output = "";
//
//		foreach($this->publicVariables as $name => $value){
//			if($value === FALSE){
//				$value = 'null';
//			}
//
//			$output .= $this->name.".prototype.".$name." = $value;\n";
//		}
//
//		foreach($this->staticVariables as $name => $value){
//			if($value === FALSE){
//				$value = 'null';
//			}
//
//			$output .= $this->name.".".$name." = $value;\n";
//		}
//
//		return $output;
//	}

	/**
	 * For class variables that are added to the class scope, but are delayed to be delcared outside
	 * the function (to be public or static) we need to grab the default values to be able to set
	 * the variables to them. Incidentally grabs any comments.
	 *
	 * @param $value
	 * @throws \Exception
	 */
	function addToVariableValue($value){
		if($this->currentVariableForConcattingValue === NULL){
			throw new \Exception("Trying to concat [$value] to the current variable - but it's not set. ");
		}

		if($this->currentVariableForConcattingValue === FALSE){
			$this->currentVariableForConcattingValue = '';
		}

		$this->currentVariableForConcattingValue .= $value;
	}
}




?>