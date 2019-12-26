<?php

/**
 * 将resources\movies 下的图片名含有inpainted的图片复制一份去掉后缀
 * tsj
 */
class copy {

	public function index() {
		try {
			$moviesID = $_POST['movies_id']; //电影id
			$chapterID = $_POST['chapter_id']; //章节id
			if (empty($moviesID) || empty($chapterID)) {
				$resArr = array(
					'code' => 400,
					'res' => 'movies_id and chapter_id Can\'t be empty'
				);
				$json = json_encode($resArr);
				echo $json;
				exit;
			}
			if (!is_numeric($moviesID) || !is_numeric($chapterID)) {
				$resArr = array(
					'code' => 400,
					'res' => 'movies_id and chapter_id Formatting error'
				);
				$json = json_encode($resArr);
				echo $json;
				exit;
			}
			$fileName = './movies/' . $moviesID . '/' . $chapterID . '/';
			if (!is_dir($fileName)) {
				$resArr = array(
					'code' => 401,
					'res' => 'file does not exist'
				);
				$json = json_encode($resArr);
				echo $json;
				exit;
			}
			$file = [];
			$resoure = opendir($fileName);
			while ($row = readdir($resoure)) {
				$file[] = $row;
			}
			$errorOne = true;
			$errorTwo = true;
			$isOk = false;
			foreach ($file as $imgName) {
				$isCopy = $this->isCopy($imgName, $fileName);
				if ((strpos($imgName, 'inpainted') > 0) && !$isCopy) {
					$res = $this->copyImg($fileName, $imgName);
					if ($res == 200) {
						$isOk = true;
					} else if ($res == 001) {
						$errorOne = false;
					} else if ($res == 002) {
						$errorTwo = false;
					}
				} else if (!$isCopy) {
					$res = $this->copyImg($fileName, $imgName);
					if ($res == 200) {
						$isOk = true;
					} else if ($res == 001) {
						$errorOne = false;
					} else if ($res == 002) {
						$errorTwo = false;
					}
				}
			}
			if ($isOk) {
				$resArr = array(
					'code' => 200,
					'res' => 'ok'
				);
				$json = json_encode($resArr);
				echo $json;
			} else if (!$errorOne) {
				$resArr = array(
					'code' => 405,
					'res' => 'No writable'
				);
				$json = json_encode($resArr);
				echo $json;
			} else if (!$errorTwo) {
				$resArr = array(
					'code' => 406,
					'res' => 'Write failure'
				);
				$json = json_encode($resArr);
				echo $json;
			} else {
				$resArr = array(
					'code' => 200,
					'res' => 'No picture matching.'
				);
				$json = json_encode($resArr);
				echo $json;
			}
		} catch (Exception $exc) {
			$resArr = array(
				'code' => 407,
				'res' => '未知错误'
			);
			$json = json_encode($resArr);
			echo $json;
		}
	}

	/**
	 * 判断是否copy 过
	 * @param type $imgNmae 图片名称
	 * @param type $fileName 文件位置
	 * @return boolean
	 */
	public function isCopy($imgNmae, $fileName) {
		$explode = explode('.', $imgNmae);
		if (isset($explode[0])) {
			if (strpos($explode[0], 'inpainted') > 0) {
				$kerFile = str_replace('-inpainted', "", $explode[0]);
			} else {
				$kerFile = $explode[0] . '-inpainted';
			}
			$overFile = $fileName . $explode[0];
			if (!file_exists($overFile) && !file_exists($kerFile)) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * 复制一份无后缀文件
	 * @param type $fileName
	 * @param type $imgName
	 * @return boolean
	 */
	public function copyImg($fileName, $imgName) {
		$file = $fileName . $imgName;
		$explode = explode('.', $imgName);
		if (strpos($explode[0], 'inpainted') <= 0) {
			$kerFile = $explode[0] . '-inpainted.' . $explode[1];
			if (file_exists($fileName . $kerFile)) {
				return false;
			}
		}
		if (isset($explode[0])) {
			if (is_writable($fileName) && is_readable($file)) {
				$copyName = $fileName . $explode[0];
				$res = copy($file, $copyName);
				$returnB = $res ? 200 : 002;
				return $returnB;
			} else {
				return 001; //不可写
			}
		} else {
			return false;
		}
	}

}

$res = new copy();
$res->index();
