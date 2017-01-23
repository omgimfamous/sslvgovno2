<?php

class CGettextPoFile
{
    protected $contextSeparator = "\004";
    
	/**
	 * Loads messages from a PO file.
	 * @param string $file file path
	 * @param string $context message context
	 * @return array message translations (source message => translated message)
	 */
	public function load($file, $context = false)
	{
		$pattern='/(msgctxt\s+"(.*?(?<!\\\\))")?'
			. '\s+msgid\s+"(.*?(?<!\\\\))"'
			. '\s+msgstr\s+"(.*?(?<!\\\\))"/';
		$content = file_get_contents($file);
        $n = preg_match_all($pattern, $content, $matches);
        // 2 - context, 3 - key, 4 - translate
        $messages = array();
        $checkContext = ($context!==false);
        $manyContexts = ($checkContext && is_array($context));
        for($i=0;$i<$n;++$i)
        {
        	if(!$checkContext || ($manyContexts ? in_array($matches[2][$i], $context) : $matches[2][$i] === $context))
        	{
	        	$id = (!$checkContext && !empty($matches[2][$i])?$matches[2][$i].$this->contextSeparator:'').$this->decode($matches[3][$i]);
	        	$message = $this->decode($matches[4][$i]);
	        	$messages[$id] = $message;
	        }
        }
        return $messages;
	}

	/**
	 * Saves messages to a PO file.
	 * @param string $file file path
	 * @param array $messages message translations (message id => translated message).
	 * Note if the message has a context, the message id must be prefixed with
	 * the context with chr(4) as the separator.
	 */
	public function save($file, $messages)
	{
		$content = "# BFF\n\n\"PO-Creation-Date: ".gmdate( 'Y-m-d H:i:s+00:00' )."\\n\"\n\"MIME-Version: 1.0\\n\"\n\"Content-Type: text/plain; charset=utf-8\\n\"\n\"Content-Transfer-Encoding: 8bit\\n\"\n\"X-Poedit-SourceCharset: utf-8\\n\"\n\n";
                    //"\"Plural-Forms: nplurals=3; plural=(n%10==1 && n%100!=11) ? 0 : ((n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20)) ? 1 : 2);\"\n\n";
                    
        foreach($messages as $id=>$message)
		{
			if(($pos=strpos($id,$this->contextSeparator))!==false)
			{
				$content.='msgctxt "'.substr($id,0,$pos)."\"\n";
				$id=substr($id,$pos+1);
			}
			$content.='msgid "'.$this->encode($id)."\"\n";
			$content.='msgstr "'.$this->encode($message)."\"\n\n";
		}
		file_put_contents($file,$content);
	}

	/**
	 * Encodes special characters in a message.
	 * @param string $string message to be encoded
	 * @return string the encoded message
	 */
	protected function encode($string)
	{
		return str_replace(array('"', "\n", "\t", "\r"),array('\\"', "\\n", '\\t', '\\r'),$string);
	}

	/**
	 * Decodes special characters in a message.
	 * @param string $string message to be decoded
	 * @return string the decoded message
	 */
	protected function decode($string)
	{
		return str_replace(array('\\"', "\\n", '\\t', '\\r'),array('"', "\n", "\t", "\r"),$string);
	}
}