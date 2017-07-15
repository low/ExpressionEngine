<?php

namespace EllisLab\Addons\Spam\Model;

use EllisLab\ExpressionEngine\Service\Model\Model;

class SpamTrap extends Model {

	protected static $_table_name = 'spam_trap';
	protected static $_primary_key = 'trap_id';

	protected static $_typed_columns = array(
		'entity'    => 'serialized',
		'trap_date' => 'timestamp',
	);

	protected static $_relationships = array(
		'Author' => array(
			'type'     => 'belongsTo',
			'model'    => 'ee:Member',
			'from_key' => 'author_id',
			'weak'     => TRUE,
			'inverse' => array(
				'name' => 'trap_id',
				'type' => 'hasMany'
			)
		)
	);

	protected $author_id;
	protected $content_type;
	protected $document;
	protected $entity;
	protected $ip_address;
	protected $optional_data;
	protected $site_id;
	protected $trap_date;
	protected $trap_id;
}

// EOF
