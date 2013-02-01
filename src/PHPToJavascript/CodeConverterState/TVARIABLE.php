<?php

namespace PHPToJavascript;

class CodeConverterState_TVARIABLE extends CodeConverterState {

	function	processToken($name, $value, $parsedToken){

		if($this->stateMachine->currentScope instanceof GlobalScope){
			$this->changeToState(CONVERTER_STATE_VARIABLE_GLOBAL);
			return TRUE;
		}

		if($this->stateMachine->currentScope instanceof FunctionScope){
			//TODO - Double-check FunctionParameterScope is meant to be the same as FunctionScope
			$this->changeToState(CONVERTER_STATE_VARIABLE_FUNCTION);
			return TRUE;
		}

		if($this->stateMachine->currentScope instanceof FunctionParameterScope){
			$this->changeToState(CONVERTER_STATE_VARIABLE_FUNCTION_PARAMETER);
			return TRUE;
		}

		if($this->stateMachine->currentScope instanceof ClassScope){
			$this->changeToState(CONVERTER_STATE_VARIABLE_CLASS);
			return TRUE;
		}
	}
}





?>