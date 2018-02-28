<?php
/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Srhinow;

use Contao\Model;

/**
 * Reads and writes Offers
 *
 * @property integer $id
 * @property integer $tstamp
 * @property string  $title
 *
 * @method static IaoOfferModel|null findById($id, $opt=array())
 * @method static IaoOfferModel|null findByIdOrAlias($val, $opt=array())
 * @method static IaoOfferModel|null findOneBy($col, $val, $opt=array())
 * @method static IaoOfferModel|null findOneByTstamp($val, $opt=array())
 * @method static IaoOfferModel|null findOneByTitle($val, $opt=array())

 *
 * @method static \Model\Collection|IaoOfferModel[]|IaoOfferModel|null findByTstamp($val, $opt=array())
 * @method static \Model\Collection|IaoOfferModel[]|IaoOfferModel|null findByTitle($val, $opt=array())
 * @method static \Model\Collection|IaoOfferModel[]|IaoOfferModel|null findBy($col, $val, $opt=array())
 * @method static \Model\Collection|IaoOfferModel[]|IaoOfferModel|null findAll($opt=array())
 *
 * @method static integer countById($id, $opt=array())
 *
 * @author Leo Feyer <https://github.com/leofeyer>
 */


class IaoProjectsModel extends Model
{
	/**
	 * Table name
	 * @var string
	 */
	protected static $strTable = 'tl_iao_projects';

	/**
	 * Find published news items by their parent ID and ID or alias
	 *
	 * @param mixed $varId      The numeric ID or alias name
	 * @param array $arrOptions An optional options array
	 *
	 * @return \Model|null The NewsModel or null if there are no news
	 */
	public static function findProjectByIdOrAlias($varId, array $arrOptions=array())
	{
		$t = static::$strTable;

		return static::findOneBy('id', $varId, $arrOptions);
	}

	/**
	 * Find bbk-items for pagination-list
	 *
	 * @param array   $filter     where-options
	 * @param array   $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no news
	 */
	public static function findProjects($intLimit=0, $intOffset=0, array $filter=array(), array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = (count($filter) > 0)? $filter : null;

		if (!isset($arrOptions['order']))
		{
			$arrOptions['order'] = "$t.id DESC";
		}

		$arrOptions['limit']  = $intLimit;
		$arrOptions['offset'] = $intOffset;


		return static::findBy($arrColumns, null, $arrOptions);
	}
	
	/**
	 * Count all project items
	 *
	 * @param array   $filter     where-options
	 * @param array   $arrOptions An optional options array
	 *
	 * @return \Model\Collection|null A collection of models or null if there are no news
	 */
	public static function countEntries(array $filter=array(), array $arrOptions=array())
	{
		$t = static::$strTable;
		$arrColumns = (count($filter) > 0)? $filter : null;

		return static::countBy($arrColumns, null, $arrOptions);
	}
}
