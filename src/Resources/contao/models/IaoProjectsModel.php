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
 * @method static IaoProjectsModel|null findById($id, $opt=array())
 * @method static IaoProjectsModel|null findByIdOrAlias($val, $opt=array())
 * @method static IaoProjectsModel|null findOneBy($col, $val, $opt=array())
 * @method static IaoProjectsModel|null findOneByTstamp($val, $opt=array())
 * @method static IaoProjectsModel|null findOneByTitle($val, $opt=array())

 *
 * @method static \Model\Collection|IaoProjectsModel[]|IaoProjectsModel|null findByTstamp($val, $opt=array())
 * @method static \Model\Collection|IaoProjectsModel[]|IaoProjectsModel|null findByTitle($val, $opt=array())
 * @method static \Model\Collection|IaoProjectsModel[]|IaoProjectsModel|null findBy($col, $val, $opt=array())
 * @method static \Model\Collection|IaoProjectsModel[]|IaoProjectsModel|null findAll($opt=array())
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
	 * @return \Model|null The IaoProjectsModel or null if there are no news
	 */
	public static function findProjectByIdOrReferenceAlias($varId, array $arrOptions=array())
	{
        $isAlias = !is_numeric($varId);

        // Try to load from the registry
        if (!$isAlias && empty($arrOptions))
        {
            $objModel = \Model\Registry::getInstance()->fetch(static::$strTable, $varId);

            if ($objModel !== null)
            {
                return $objModel;
            }
        }

        $t = static::$strTable;

        $arrOptions = array_merge
        (
            array
            (
                'limit'  => 1,
                'column' => $isAlias ? array("$t.reference_alias=?") : array("$t.id=?"),
                'value'  => $varId,
                'return' => 'Model'
            ),

            $arrOptions
        );

        return static::find($arrOptions);
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
