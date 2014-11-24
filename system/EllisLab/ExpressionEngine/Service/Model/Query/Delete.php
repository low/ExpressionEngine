<?php
namespace EllisLab\ExpressionEngine\Service\Model\Query;

use EllisLab\ExpressionEngine\Service\Model\Relation\BelongsTo;

class Delete extends Query {

	const DELETE_BATCH_SIZE = 100;

	public function run()
	{
		$builder = $this->builder;
		$from = $this->builder->getFrom();
		$frontend = $builder->getFrontend();

		$from_pk = $this->store->getMetaDataReader($from)->getPrimaryKey();

		$delete_list = $this->getDeleteList($from);
		$parent_ids = $this->getParentIds($from, $from_pk);

		foreach ($delete_list as $model => $withs)
		{
			$offset		= 0;
			$batch_size = self::DELETE_BATCH_SIZE; // TODO change depending on model?

			do {
				// TODO yuck. The relations have this info more correctly
				// in their to and from keys. store that instead.
				$to_pk = $this->store->getMetaDataReader($model)->getPrimaryKey();

				$delete_ids = $builder
					->getFrontend()
					->get($model)
					->with($withs)
					->fields("{$model}.{$to_pk}")
					->filter("{$from}.{$from_pk}", 'IN', $parent_ids)
					->offset($offset)
					->limit($batch_size)
					->all()
					->pluck($to_pk);


				$offset += $batch_size;

				if ( ! count($delete_ids))
				{
					continue;
				}

				$this->deleteAsLeaf($model, $delete_ids);
			}
			while (count($delete_ids) == $batch_size);
		}
	}

	/**
	 * Delete the model and its tables, ignoring any relationships
	 * that might exist. This is a utility function for the main
	 * delete which *is* aware of relationships.
	 *
	 * @param String $model       Model name to delete from
	 * @param Int[]  $delete_ids  Array of primary key ids to remove
	 */
	protected function deleteAsLeaf($model, $delete_ids)
	{
		$reader = $this->store->getMetaDataReader($model);

		$tables = array_keys($reader->getTables());
		$key = $reader->getPrimaryKey();

		$this->store->rawQuery()
			->where_in($key, $delete_ids)
			->delete($tables);
	}

	/**
	 *
	 */
	protected function getParentIds($from, $from_pk)
	{
		$builder = clone $this->builder;
		return $builder
			->fields("{$from}.{$from_pk}")
			->all()
			->pluck($from_pk);
	}
	/**
	 * I need a list for each child model name to delete that contains all
	 * withs that lead back to the parent being deleted. Ideally those
	 * will be in the order I need to process them in:
	 *
	 * get('Site')->delete()
	 *
	 * Template->with(array('TemplateGroup' => array('Site')));
	 * TemplateGroup->with(array('Site'));
	 * Site->with();
	 *
	 * So this:
	 *
	 * array(
	 *    'Template'      => array('TemplateGroup' => array('Site' => array()))
	 *    'TemplateGroup' => array('Site' => array())
	 *    'Site'          => array()
	 * );
	 *
	 */
	protected function getDeleteList($from)
	{
		$this->delete_list = array();
		$this->deleteListRecursive($from);
		return array_reverse($this->delete_list);
	}

	/**
	 *
	 */
	protected function deleteListRecursive($parent)
	{
		$results = array();
		$relations = $this->store->getAllRelations($parent);

		if ( ! isset($this->delete_list[$parent]))
		{
			$this->delete_list[$parent] = array();
		}

		foreach ($relations as $name => $relation)
		{
			$inverse = $relation->getInverse();

			if ($inverse instanceOf BelongsTo)
			{
				$to_name = $inverse->getName();
				$to_model = $relation->getTargetModel();

				if ( ! isset($this->delete_list[$to_model]))
				{
					$this->delete_list[$to_model] = array();
				}

				$inherit = $this->delete_list[$parent];
				$this->delete_list[$to_model][$to_name] = $inherit;

				$this->deleteListRecursive($to_model);
			}
		}

		return $results;
	}
}