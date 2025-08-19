<?php
/**
 * MetaSet interface
 * Copyright (c) TIV.NET INC 2021.
 */

namespace WOOMC\Dependencies\TIVWP\Abstracts;

/**
 * Interface MetaSetInterface
 */
interface MetaSetInterface {

	/**
	 * Returns the MetaSet fields.
	 *
	 * @return array[]
	 */
	public function get_meta_fields(): array;

	/**
	 * Returns the MetaSet title.
	 *
	 * @return string
	 */
	public function get_title(): string;

	/**
	 * Returns the MetaSet type.
	 *
	 * @return string
	 */
	public function get_type(): string;
}
