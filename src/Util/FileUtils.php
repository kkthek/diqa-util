<?php
namespace DIQA\Util;

class FileUtils {
	
	/**
	 * Shows the last lines of a text-file.
	 * 
	 * @param string $path
	 * @param int $line_count
	 * @param int $block_size
	 * @return multitype:
	 */
	public static function last_lines($path, $line_count, $block_size = 512){
		$lines = array();
	
		// we will always have a fragment of a non-complete line
		// keep this in here till we have our next entire line.
		$leftover = "";
	
		$fh = fopen($path, 'r');
		// go to the end of the file
		fseek($fh, 0, SEEK_END);
		do{
			// need to know whether we can actually go back
			// $block_size bytes
			$can_read = $block_size;
			if(ftell($fh) < $block_size){
				$can_read = ftell($fh);
			}
	
			// go back as many bytes as we can
			// read them to $data and then move the file pointer
			// back to where we were.
			fseek($fh, -$can_read, SEEK_CUR);
			$data = fread($fh, $can_read);
			$data .= $leftover;
			fseek($fh, -$can_read, SEEK_CUR);
	
			// split lines by \n. Then reverse them,
			// now the last line is most likely not a complete
			// line which is why we do not directly add it, but
			// append it to the data read the next time.
			$split_data = array_reverse(explode("\n", $data));
			$new_lines = array_slice($split_data, 0, -1);
			$lines = array_merge($lines, $new_lines);
			$leftover = $split_data[count($split_data) - 1];
		}
		while(count($lines) < $line_count && ftell($fh) != 0);
		if(ftell($fh) == 0){
			$lines[] = $leftover;
		}
		fclose($fh);
		// Usually, we will read too many lines, correct that here.
		return array_slice($lines, 0, $line_count);
	}
}