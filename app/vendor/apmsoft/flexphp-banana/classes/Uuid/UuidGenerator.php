<?php
namespace Flex\Banana\Classes\Uuid;

class UuidGenerator
{
	public const __version = '1.2';
	public function __construct(){
	}

	#@ String
	public function v3(string $uuid, string $keyname) : mixed
	{
		if(!$this->is_valid($uuid)) return false;

        $nhex = str_replace(array('-','{','}'), '', $uuid);
        $nstr = '';

        for($i = 0; $i < strlen($nhex); $i+=2) {
            $nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
        }

        $hash = md5($nstr . $keyname);

        return sprintf('%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	#@ String
	# GenerateUUID V4
	# prekey 에 timestamp, ymdhis, microtime(date('YmdHis') . substr((string)microtime(), 2, 6)) 를 사용할 경우 asc, desc 정렬이 가능함
	public function v4(?string $prekey=null)
	{
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand(0, 0xffff), mt_rand(0, 0xffff),
			mt_rand(0, 0xffff),
			mt_rand(0, 0x0fff) | 0x4000,
			mt_rand(0, 0x3fff) | 0x8000,
			mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
		);

		return ($prekey !== null && $prekey) ? $prekey.'-'.$uuid : $uuid;
	}

	#@ String
	public function v5(string $uuid, string $keyname) : mixed
	{
		if(!$this->is_valid($uuid)) return false;

		$nhex = str_replace(array('-','{','}'), '', $uuid);
		$nstr = '';

		for($i = 0; $i < strlen($nhex); $i+=2) {
			$nstr .= chr(hexdec($nhex[$i].$nhex[$i+1]));
		}

		$hash = sha1($nstr . $keyname);

		return sprintf('%08s-%04s-%04x-%04x-%12s',
			substr($hash, 0, 8),
			substr($hash, 8, 4),
			(hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,
			(hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,
			substr($hash, 20, 12)
		);
	}

	#@ String
	public function is_valid(string $uuid) : mixed {
		return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?'.'[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
	}
}