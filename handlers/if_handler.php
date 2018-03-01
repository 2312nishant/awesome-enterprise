<?php

//////// Module Library ///////////////////
aw2_library::add_library('if','If Functions');

function aw2_if_equal($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['equal']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_not_equal($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['not_equal']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_greater_equal($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['greater_equal']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_greater_than($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['greater_than']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_less_equal($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['less_equal']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_less_than($atts,$content=null,$shortcode){
	$atts['cond']=$atts['lhs'];
	$atts['less_than']=$atts['rhs'];
	unset($atts['lhs']);
	unset($atts['rhs']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_else($atts,$content=null,$shortcode){
	if(aw2_library::pre_actions('all',$atts,$content)==false)return;
	$stack_id=aw2_library::last_child('if');
	$call_stack=&aw2_library::get_array_ref('call_stack',$stack_id);
	$status=$call_stack['status'];
	if($status)
		$return_value= '';
	else
		$return_value= aw2_library::parse_shortcode($content);

	aw2_library::pop_child($stack_id);
	return aw2_library::post_actions('all',$return_value,$atts);

}

function aw2_if_and($atts,$content=null,$shortcode){
	aw2_library::pre_actions('parse_attributes',$atts,$content,$shortcode);
	extract( shortcode_atts( array(
		'main' => null
	), $atts) );

	
	if($main){
		$atts['cond']=isset($atts['lhs']) ? $atts['lhs'] : null;
		$atts[$main]=isset($atts['rhs']) ? $atts['rhs'] : null;
		
		if(isset($atts['lhs'])) { unset($atts['lhs']); }
		if(isset($atts['rhs'])) { unset($atts['rhs']); }
		unset($atts['main']);
	}

	$cond=aw2_library::pre_actions('check_if',$atts,$content,$shortcode);

	$stack_id=aw2_library::last_child('if');
	$call_stack=&aw2_library::get_array_ref('call_stack',$stack_id);
	$status=$call_stack['status'];
	if(is_null($status)){
		aw2_library::set_error('And without If');
		return;
	}
	
	if($cond==true && $status==true){
		$status=true;
		$return_value= aw2_library::parse_shortcode($content);
	}
	else{
		$return_value= '';
		$status=false;
	}
	$call_stack['status']=$status;
	return aw2_library::post_actions('all',$return_value,$atts);
}

function aw2_if_or($atts,$content=null,$shortcode){
	aw2_library::pre_actions('parse_attributes',$atts,$content,$shortcode);
	extract( shortcode_atts( array(
		'main' => null
	), $atts) );

	
	if($main){
		$atts['cond']=$atts['lhs'];
		$atts[$main]=$atts['rhs'];
		
		unset($atts['lhs']);
		unset($atts['rhs']);
		unset($atts['main']);
	}

	$cond=aw2_library::pre_actions('check_if',$atts,$content,$shortcode);

	$stack_id=aw2_library::last_child('if');
	$call_stack=&aw2_library::get_array_ref('call_stack',$stack_id);
	$status=$call_stack['status'];

	if(is_null($status)){
		aw2_library::set_error('or without If');
		return;
	}
	
	if($cond==true || $status==true){
		$status=true;
		$return_value= aw2_library::parse_shortcode($content);
	}
	else{
		$return_value= '';
		$status=false;
	}

	$call_stack['status']=$status;
	return aw2_library::post_actions('all',$return_value,$atts);

}

function aw2_if_unhandled($atts,$content=null,$shortcode){
	if(aw2_library::pre_actions('all',$atts,$content)==false)return;
	extract( shortcode_atts( array(
	'main'=>null
	), $atts) );
	$pieces=$shortcode['tags'];
	if(count($pieces)!=2)return 'error:You must have exactly two parts to the if shortcode';
	switch ($pieces[1]) {
		case 'whitespace':
			$atts['whitespace']=aw2_library::get($main);
			break;
		case 'not_whitespace':
			$atts['not_whitespace']=aw2_library::get($main);
			break;
		case 'false':
			$atts['false']=aw2_library::get($main);
			break;
		case 'true':
			$atts['true']=aw2_library::get($main);
			break;
		case 'yes':
			$atts['yes']=aw2_library::get($main);
			break;
		case 'no':
			$atts['no']=aw2_library::get($main);
			break;
			
		case 'not_empty':
			$atts['not_empty']=aw2_library::get($main);
			break;
		case 'empty':
			$atts['empty']=aw2_library::get($main);
			break;
		case 'odd':
			$atts['odd']=aw2_library::get($main);
			break;			
		case 'even':
			$atts['even']=aw2_library::get($main);
			break;	
			
		case 'arr':
			$atts['arr']=aw2_library::get($main);
			break;	
		case 'not_arr':
			$atts['not_arr']=aw2_library::get($main);
			break;	
		case 'str':
			$atts['str']=aw2_library::get($main);
			break;	
		case 'not_str':
			$atts['not_str']=aw2_library::get($main);
			break;	
		case 'bool':
			$atts['bool']=aw2_library::get($main);
			break;	
		case 'not_bool':
			$atts['not_bool']=aw2_library::get($main);
			break;	
		case 'num':
			$atts['num']=aw2_library::get($main);
			break;	
		case 'not_num':
			$atts['not_num']=aw2_library::get($main);
			break;	
		case 'int':
			$atts['int']=aw2_library::get($main);
			break;	
		case 'not_int':
			$atts['not_int']=aw2_library::get($main);
			break;	
		case 'date_obj':
			$atts['date_obj']=aw2_library::get($main);
			break;	
		case 'not_date_obj':
			$atts['not_date_obj']=aw2_library::get($main);
			break;	
		case 'obj':
			$atts['obj']=aw2_library::get($main);
			break;	
		case 'not_obj':
			$atts['not_obj']=aw2_library::get($main);
			break;	
			
		case 'user_can':
			$atts['user_can']=$main;
			break;		
		case 'user_cannot':
			$atts['user_cannot']=$main;
			break;		
		case 'logged_in':
			$atts['logged_in']='';
			break;		
		case 'not_logged_in':
			$atts['not_logged_in']='';
			break;		
		case 'request':
			$atts['request_exists']=$main;
			break;		
		case 'not_request':
			$atts['not_request_exists']=$main;
			break;		
		case 'device':
			$atts['device']=$main;
			break;				
	}
	unset($atts['main']);	
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
	
}

function aw2_if_contains($atts,$content=null,$shortcode){
	$atts['contains']=$atts['needle'];
	$atts['list']=$atts['haystack'];
	unset($atts['needle']);
	unset($atts['haystack']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}

function aw2_if_not_contains($atts,$content=null,$shortcode){
	$atts['not_contains']=$atts['needle'];
	$atts['list']=$atts['haystack'];
	unset($atts['needle']);
	unset($atts['haystack']);
	$return_value=aw2_if_helper($atts,$content,$shortcode);
	return $return_value;
}


function aw2_if_helper($atts,$content=null,$shortcode){
	$cond=aw2_library::pre_actions('all',$atts,$content,$shortcode);
	extract( shortcode_atts( array(
		'main' => null
	), $atts) );
	
	$stack_id=aw2_library::push_child('if','if');
	$call_stack=&aw2_library::get_array_ref('call_stack',$stack_id);
	
	if($main){
		$check=aw2_library::get($main);
		if($check==false)
			$cond=false;
	}
	
	$return_value= '';
	
	if($cond==true){
		$status=true;
		$return_value= aw2_library::parse_shortcode($content);
	}
	else
		$status=false;
	
	$call_stack['status']=$status;	
	return aw2_library::post_actions('all',$return_value,$atts);
}
