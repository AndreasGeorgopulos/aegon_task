<?php
namespace Language\Singleton;

interface ISingleton
{
	public static function getInstance(): ISingleton;
}