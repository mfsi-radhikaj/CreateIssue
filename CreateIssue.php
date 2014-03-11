<?php
/*
 * This file implements the submission of issue for github and bitbucket from command line script 
 */

// constants for defining the api url
define("GITHUB_API_URL", "https://api.github.com/repos/");
define("BITBUCKET_API_URL", "https://bitbucket.org/api/1.0/repositories/");
 
// get the commandline argument
$username = $argv[1];
$password = $argv[2];
$repositoryUrl = $argv[3];
$issueTitle = $argv[4];
$issueDescription = $argv[5];

// check if any of the input parameter from command line is missing

if(trim($username) !== '' && trim($password) !== '' && trim($repositoryUrl) !== ''
	&& trim($issueTitle) !== '' && trim($issueDescription) !== '') {

	$obj = new SubmitIssue();
	$repositoryObj = $obj->GetRepositoryObject($repositoryUrl);

	$repositoryObj->CreateIssue($repositoryUrl, $username, $password, $issueTitle, $issueDescription);
}
else {
	echo 'Please provide all the parameters';
}

/*
 * This class submit the issue to repository
 */
class SubmitIssue {

	/*
	 * creates the github or bitbucket class object depending on the 
	 */
	public function GetRepositoryObject($url) {

		if(strpos($url, 'github') !== false) { // check if its a github url then return github object
			$repositoryObj = new GithubClass();
		}
		else if(strpos($url, 'bitbucket') !== false) { // check if its a bitbucket url then return bitbucket object
			$repositoryObj = new BitbucketClass();
		}
		else { // send proper message if incorrect repository url
			echo 'Sorry!!! Wrong repository url';
			return false;
		}
		return $repositoryObj;
	}

	/*
	 * This function posts the data to repository url for issue
	 * @param $url - repositiory url
	 * @param $username - username by which you want to submit issue
	 * @param $password - password
	 * @param $title - title of the issue
	 * @param $description - description of the issue
	 */
	protected function SubmitIssueFunction($repositoryUrl, $username, $password, $dataToPost) {

		try {
			$handler = curl_init($repositoryUrl);
			curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($handler, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($handler, CURLOPT_USERAGENT, 'Mozilla');
			curl_setopt($handler, CURLOPT_POST, true);
			curl_setopt($handler, CURLOPT_POSTFIELDS, $dataToPost);
			curl_setopt($handler, CURLOPT_USERPWD, "$username:$password");
			curl_exec($handler);

			if(curl_error($handler)) {
				file_put_contents('error_log.txt', 'Error occured while trying to submit issue : '.curl_error($handler), FILE_APPEND);
				echo 'Sorry!!! Some error occured while trying to submit issue to the repository';
			}
			else {
				echo 'Successfully posted the issue.';
			}
			curl_close($handler);
		}
		catch(Exception $e) {
			file_put_contents('error_log.txt', 'Error occured while trying to submit issue : '.$e->getMessage(), FILE_APPEND);
			echo 'Sorry!!! Some error occured while trying to submit issue to the repository';
			return false;
		}
	}
}

/*
 * This class is used for providing proper formatted data to SubmitIssueFunction() required by github repository
 */
class GithubClass extends SubmitIssue {

	/*
	 * This function formats the data and provides it to SubmitIssueFunction() for github repository
	 * @param $repositoryUrl - repositiory url
	 * @param $username - username by which you want to submit issue
	 * @param $password - password
	 * @param $title - title of the issue
	 * @param $description - description of the issue
	 */
	public function CreateIssue($repositoryUrl, $username, $password, $title, $description) {
		$dataToPostArr['title'] = trim($title);
		$dataToPostArr['body'] = trim($description); // escape input
		$dataToPost = json_encode($dataToPostArr);

		$repositoryUrlArr = explode('/', rtrim($repositoryUrl, '/'));
		$repository = array_pop($repositoryUrlArr);
		$user = array_pop($repositoryUrlArr);

		$repositoryUrl = GITHUB_API_URL.$user.'/'.$repository.'/issues';

		$this->SubmitIssueFunction($repositoryUrl, $username, $password, $dataToPost);
	}
}

/*
 * This class is used for providing proper formatted data to SubmitIssueFunction() required by bitbucket repository
 */
class BitbucketClass extends SubmitIssue {

	/*
	 * This function formats the data and provides it to SubmitIssueFunction() for bitbucket repository
	 * @param $repositoryUrl - repositiory url
	 * @param $username - username by which you want to submit issue
	 * @param $password - password
	 * @param $title - title of the issue
	 * @param $description - description of the issue
	 */
	public function CreateIssue($repositoryUrl, $username, $password, $title, $description) {
		$dataToPost['title'] = trim($title);
		$dataToPost['content'] = trim($description);// escape input

		$repositoryUrlArr = explode('/', rtrim($repositoryUrl, '/'));
		$repository = array_pop($repositoryUrlArr);
		$user = array_pop($repositoryUrlArr);

		$repositoryUrl = BITBUCKET_API_URL.$user.'/'.$repository.'/issues';

		$this->SubmitIssueFunction($repositoryUrl, $username, $password, $dataToPost);
	}
}