<?php
/**
 *
 * This file is part of the phpBB Forum Software package.
 *
 * @copyright (c) phpBB Limited <https://www.phpbb.com>
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * For full copyright and license information, please see
 * the docs/CREDITS.txt file.
 *
 */

namespace phpbb;

class version_helper_remote_test extends \phpbb_test_case
{
	static $remote_data = '';
	protected $cache;
	protected $version_helper;

	public function setUp()
	{
		parent::setUp();

		global $phpbb_root_path, $phpEx;

		include_once($phpbb_root_path . 'includes/functions.' . $phpEx);

		$config = new \phpbb\config\config(array(
			'version'	=> '3.1.0',
		));
		$container = new \phpbb_mock_container_builder();
		$db = new \phpbb\db\driver\factory($container);
		$this->cache = $this->getMock('\phpbb\cache\service', array('get'), array(new \phpbb\cache\driver\null(), $config, $db, '../../', 'php'));
		$this->cache->expects($this->any())
			->method('get')
			->with($this->anything())
			->will($this->returnValue(false));

		$this->version_helper = new \phpbb\version_helper(
			$this->cache,
			$config,
			new \phpbb\user('\phpbb\datetime')
		);
		$this->user = new \phpbb\user('\phpbb\datetime');
		$this->user->add_lang('acp/common');
	}

	public function provider_get_versions()
	{
		return array(
			array('', false),
			array('foobar', false),
			array('{
    "stable": {
        "1.0": {
            "current": "1.0.1",
            "download": "https://www.phpbb.com/customise/db/download/104136",
            "announcement": "https://www.phpbb.com/customise/db/extension/boardrules/",
            "eol": null,
            "security": false
        }
    }
}', true, array (
				'stable' => array (
					'1.0' => array (
						'current' => '1.0.1',
						'download' => 'https://www.phpbb.com/customise/db/download/104136',
						'announcement' => 'https://www.phpbb.com/customise/db/extension/boardrules/',
						'eol' => NULL,
						'security' => false,
					),
				),
				'unstable' => array (
					'1.0' => array (
						'current' => '1.0.1',
						'download' => 'https://www.phpbb.com/customise/db/download/104136',
						'announcement' => 'https://www.phpbb.com/customise/db/extension/boardrules/',
						'eol' => NULL,
						'security' => false,
					),
				),
			)),
			array('{
    "foobar": {
        "1.0": {
            "current": "1.0.1",
            "download": "https://www.phpbb.com/customise/db/download/104136",
            "announcement": "https://www.phpbb.com/customise/db/extension/boardrules/",
            "eol": null,
            "security": false
        }
    }
}', false),
			array('{
    "stable": {
        "1.0": {
            "current": "1.0.1<script>alert(\'foo\');</script>",
            "download": "https://www.phpbb.com/customise/db/download/104136<script>alert(\'foo\');</script>",
            "announcement": "https://www.phpbb.com/customise/db/extension/boardrules/<script>alert(\'foo\');</script>",
            "eol": "<script>alert(\'foo\');</script>",
            "security": "<script>alert(\'foo\');</script>"
        }
    }
}', true, array (
				'stable' => array (
					'1.0' => array (
						'current' => '1.0.1&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'download' => 'https://www.phpbb.com/customise/db/download/104136&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'announcement' => 'https://www.phpbb.com/customise/db/extension/boardrules/&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'eol' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'security' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
					),
				),
				'unstable' => array (
					'1.0' => array (
						'current' => '1.0.1&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'download' => 'https://www.phpbb.com/customise/db/download/104136&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'announcement' => 'https://www.phpbb.com/customise/db/extension/boardrules/&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'eol' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'security' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
					),
				),
			)),
			array('{
    "unstable": {
        "1.0": {
            "current": "1.0.1<script>alert(\'foo\');</script>",
            "download": "https://www.phpbb.com/customise/db/download/104136<script>alert(\'foo\');</script>",
            "announcement": "https://www.phpbb.com/customise/db/extension/boardrules/<script>alert(\'foo\');</script>",
            "eol": "<script>alert(\'foo\');</script>",
            "security": "<script>alert(\'foo\');</script>"
        }
    }
}', true, array (
				'unstable' => array (
					'1.0' => array (
						'current' => '1.0.1&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'download' => 'https://www.phpbb.com/customise/db/download/104136&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'announcement' => 'https://www.phpbb.com/customise/db/extension/boardrules/&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'eol' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
						'security' => '&lt;script&gt;alert(\'foo\');&lt;/script&gt;',
					),
				),
				'stable' => array(),
			)),
		);
	}

	/**
	 * @dataProvider provider_get_versions
	 */
	public function test_get_versions($input, $valid_data, $expected_return = '')
	{
		self::$remote_data = $input;

		if (!$valid_data)
		{
			try {
				$return = $this->version_helper->get_versions();
			} catch (\RuntimeException $e) {
				$this->assertEquals((string)$e->getMessage(), $this->user->lang('VERSIONCHECK_FAIL'));
			}
		}
		else
		{
			$return = $this->version_helper->get_versions();
		}

		$this->assertEquals($expected_return, $return);
	}
}

/**
 * Mock function for get_remote_file()
 */
function get_remote_file($host, $path, $file, $errstr, $errno)
{
	return \phpbb\version_helper_remote_test::$remote_data;
}
