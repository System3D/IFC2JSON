<?php

namespace System3D\IFC2JSON;

/**
 * Converte as coisa
 */
class IFC2JSON
{

	var $file,
		$minify;

	function __construct($file = null, $minify = false)
	{		
		$this->file 	= $file;		
		$this->minify 	= $minify;		
	}


	/**
	 * [convert description]
	 * @return [type] [description]
	 */
    public function convertFile( $file = null )
    {
    	if( is_file($file) ){
			return $file . ' Ta convertido!';    	    	
    	} else
    	if( $this->file ){    		
    		return $this->file . ' Ta convertido!';    	    		
    	}
    	
    	return 'Converter o que?';
    	
    }

    /**
     * [getJson description]
     * @param  [type] $file [description]
     * @return [type]       [description]
     */
    public function getJson( $file = null )
    {
    	if ( is_file($file) ) {
    		return $this->readIFC( $file );
    		// return 'Convertendo e cuspindo json do arquivo '.$file.'...';
    	}
    	
    	if (!$this->file) {
    		return 'Informe o arquivo';
    	}
    	
    	$data 		= $this->readIFC( $this->file );	
    	
    	if( $this->minify ){
    		$minified 	= $this->minify( $data );	
    		return $minified;
    	}
    	
    	return $data;
    }


    public function readIFC( $file )
    {    
    	if( !is_file( $file ) )
    		return "Arquivo não encontrado!";    	

    	$handle = fopen( $file, "r");
		if ($handle) {

			$readingsection = '';

			$ifc = [];

		    while (($line = fgets($handle)) !== false) {        

		        // FIM SEÇÃO ?
		        if( 'ENDSEC;' == substr( $line, 0, 7) ) {
		        	// Fim seção    	
		        	$readingsection = NULL;        	
		        }


		        /* ------------------------------------ */

		        // SEÇÃO HEADER
		        if( 'HEADER' == $readingsection ){
		        	
		        	$ifc['HEADER'][] = $line;

		        }; 
		        		       
		        // SEÇÃO DATA
		        if( 'DATA' == $readingsection ){

		        	$item = explode('=', $line);
		        	$ifc['DATA'][ $item[0] ] = $item[1];

		        }; 
		        
		        /* ------------------------------------ */

		        
		        // LENDO QUAL SEÇÃO ?
		        if( 'DATA;' == substr( $line, 0, 5) ) {
		        	// Seção DATA
		        	$readingsection = 'DATA';
		        }else 
		        if( 'HEADER;' == substr( $line, 0, 7) ) {
		        	// Seção HEADER
		        	$readingsection = 'HEADER';
		        }
		        
		    }

		    // Fecha o arquivo
		    fclose($handle);


		    // --------------------------------------------------------

		    
		    // Conversão
		    foreach ($ifc['DATA'] as $ponteiro => $data) {		    	
		    	$ifc['DATA'][ $ponteiro ] = $this->getConverted( $data );
		    }
		 

		} else {
		    // error opening the file.
		} 


		// $ifc = json_encode( $ifc );
	   	   
	    return  $ifc;	  

	}

	/**
	 * TEJE CONVERTIDO!!!
	 * Se for um array
	 * 		Chama a função convert() várias vezes
	 * Se não for array
	 * 		Chama convert() uma vez
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
	public function getConverted( $input){
	    $out = [];
	    if( is_array($input) ){
		    foreach ($input as $key => $val) {				
	    		// print_r($val);
		    	$out[] = $this->convert($val);
		    }
	    	// dump( $out );
	    	// die;
		    return $out;
	    }else{
			return $this->convert($input);
	    }

    	
    }

	/**
     * Converte para Array
     * @param  [type] $value [description]
     * @return Array        [description]
     */
    public function convert($value){
		
    	# "IFCPROPERTYSINGLEVALUE('Part Mark',$,IFCLABEL('M3','M3','M1','M3','M2'),$);\r\n"	    		    	

    	if( substr_count($value, '(') ){	    		
    		
    		$line 		= explode('(', $value, 2);
    		
    		//	PARAMETRO	    	
			$parameter 	= $line[0];	

    		$values = str_replace(');', '', @$line[1]); // Remove ');' 
    		$values = str_replace(';', '', $values); // Remove ';' 	    		
	    	$values = str_replace("'", '', $values); 	    	
    	
	    	
	    	# "'Part Mark',$,IFCLABEL('M3','M3','M1','M3','M2'),$"	    	
	    	
	    	
	    	// Tira e salva tudo o que há entre parênteses
	    	// 	(pra poder dar o explode na vírgula)
	    	$inParenthesis = $this->getInParenthesis( $values );
	    	
	    	$v = str_replace( $inParenthesis , '%R%', $values );
	    	

	    	# "'Part Mark',$,IFCLABEL(%R%),$\r\n"	    	
	    	
	    	$v = explode(',', $v);

	    	# ['Part Mark', $, IFCLABEL(%R%), $\r\n]"

	    	foreach ($v as $key => $val) {
	    		
	    		$val = $this->cleanLine($val);

	    		if( substr_count($val, '%R%') ){

	    			// Coloca denovo o conteúdo entre parênteses
	    			if( is_array( $inParenthesis[0] ) ){
	    				$val = str_replace( '%R%', $inParenthesis[0][0], $val );
	    			}else{
	    				$val = str_replace( '%R%', $inParenthesis[0], $val );
	    			}
							    			
					// Confere se ainda existe parenteses para convertes para array
	    			if( substr_count($val, ')') ){

		    			$valarray 	= $this->getConverted( $val );				    			

						$v[ $key ] 	= $valarray;
					}else{
						$v[ $key ] 	= $val;
					}
					
	    		}else{
	    			$v[ $key ] = $val;			    			
	    		}
	    	}	    			   
	    	
	    	// $v = json_encode( $v );
	    	// $v = json_decode( $v );

	    	// Retorna Array...
	    	if( $parameter != "" ){
	    		// ...Com chave	    				
	    		return [ $parameter => $v ];	# ['IFCPROPERTYSINGLEVALUE' => ['Part Mark', $, IFCLABEL('M3','M3','M1','M3','M2'), $] ]
	    	}else{
	    		// ...Sem chave	    				
    			return $v;					# ['Part Mark', $, IFCLABEL('M3','M3','M1','M3','M2'), $]	    		
	    	}			
	 
    	}else{

    		// Não há parênteses
			
			$values = $value;
			$values = str_replace("'", '"', $values); // Remove Aspas simples e coloca aspas duplas    			    		

			return [ $values ];
		}    	

   	    

    }


	/**
	 * Remove novas linhas e parenteses excedentes
	 * @param  [type] $input [description]
	 * @return [type]        [description]
	 */
    private function cleanLine($input)
    {		    
	    $search = array("\r\n");
	    $replace = array('');

	    if( substr_count($input, ')') && !substr_count($input, '(') ){
			$search[] 	= ")";
			$replace[] 	= "";	    		
    	}
	    
	    return str_replace($search, $replace, $input);
    }


    /**
     * Extrai o que há entre parenteses
     * @param  [type] $values [description]
     * @return [type]         [description]
     */
	private function getInParenthesis($values)
	{					
		$string = $values;

			$regex = '#\((([^()]+|(?R))*)\)#';
			if (preg_match_all($regex, $string ,$matches)) {	
			    				    
			   	return $matches[1];				    	

			} else {
			    //no parenthesis
			    return NULL;
			}			

		if( substr_count($string, '(') < 2 ){
		}else {
		    //no parenthesis
		    return NULL;
		}

	}


	public function minify($data)
	{
		dump( $data );
		$ifclosedshell = $data;
	}

}