<?php
/**
 * @package   OSMeta
 * @contact   www.alledia.com, support@alledia.com
 * @copyright 2013-2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

namespace Alledia\OSMeta\Free\Container\Component;

use Alledia\OSMeta\Free\Container\AbstractContainer;
use JRequest;
use JFactory;
use JHtml;
use JText;
use JModelLegacy;
use stdClass;
use JRoute;
use ContentHelperRoute;
use JUri;

// No direct access
defined('_JEXEC') or die();

if (!class_exists('ContentHelperRoute')) {
    require JPATH_SITE . '/components/com_content/helpers/route.php';
}

/**
 * Article Metatags Container
 *
 * @since  1.0
 */
class Content extends AbstractContainer
{
    /**
     * Code
     *
     * @var    int
     * @since  1.0
     */
    public $code = 1;

    /**
     * Get Meta Tags
     *
     * @param int $lim0   Offset
     * @param int $lim    Limit
     * @param int $filter Filter
     *
     * @access  public
     *
     * @return array
     */
    public function getMetatags($lim0, $lim, $filter = null)
    {
        $db = JFactory::getDBO();
        $sql = "SELECT SQL_CALC_FOUND_ROWS c.id, c.title,
            c.metadesc, m.title as metatitle, c.alias, c.catid
            FROM `#__content` c
            LEFT JOIN `#__categories` cc ON cc.id=c.catid
            LEFT JOIN `#__osmeta_metadata` m ON m.item_id=c.id and m.item_type=1 WHERE 1";

        $search = JRequest::getVar("com_content_filter_search", "");
        $catId = JRequest::getVar("com_content_filter_catid", "0");
        $level = JRequest::getVar("com_content_filter_level", "0");
        $authorId = JRequest::getVar("com_content_filter_authorid", "0");
        $state = JRequest::getVar("com_content_filter_state", "");
        $access = JRequest::getVar("com_content_filter_access", "");

        $comContentFilterShowEmptyDescriptions = JRequest::getVar("com_content_filter_show_empty_descriptions", "-1");

        if ($search != "") {
            if (is_numeric($search)) {
                $sql .= " AND c.id=" . $db->quote($search);
            } else {
                $sql .= " AND c.title LIKE " . $db->quote('%' . $search . '%');
            }
        }

        $baselevel = 1;

        if ($catId > 0) {
            $db->setQuery("SELECT * from #__categories where id=" . $db->quote($catId));
            $cat_tbl = $db->loadObject();
            $rgt = $cat_tbl->rgt;
            $lft = $cat_tbl->lft;
            $baselevel = (int) $cat_tbl->level;
            $sql .= ' AND cc.lft >= ' . (int) $lft;
            $sql .= ' AND cc.rgt <= ' . (int) $rgt;
        }

        if ($level > 0) {
            $sql .= ' AND cc.level <=' . ((int) $level + (int) $baselevel - 1);
        }

        if ($authorId > 0) {
            $sql .= " AND c.created_by=" . $db->quote($authorId);
        }

        switch ($state) {
            case 'P':
                $sql .= " AND c.state=1";
                break;

            case 'U':
                $sql .= " AND c.state=0";
                break;

            case 'A':
                $sql .= " AND c.state=-1";
                break;

            case 'D':
                $sql .= " AND c.state=-2";
                break;

            case 'All':
                break;

            default:
                $sql .= " AND c.state=1";
                break;
        }

        if ($comContentFilterShowEmptyDescriptions != "-1") {
            $sql .= " AND (ISNULL(c.metadesc) OR c.metadesc='') ";
        }

        if (!empty($access)) {
            $sql .= " AND c.access = " . $db->quote($access);
        }

        // Sorting
        $order = JRequest::getCmd("filter_order", "title");
        $order_dir = JRequest::getCmd("filter_order_Dir", "ASC");

        switch ($order) {
            case "meta_title":
                $sql .= " ORDER BY metatitle ";
                break;

            case "meta_desc":
                $sql .= " ORDER BY metadesc ";
                break;

            default:
                $sql .= " ORDER BY title ";
                break;

        }

        $order_dir = strtoupper($order_dir);

        if ($order_dir === "ASC") {
            $sql .= " ASC";
        } else {
            $sql .= " DESC";
        }

        $db->setQuery($sql, $lim0, $lim);
        $rows = $db->loadObjectList();

        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        for ($i = 0; $i < count($rows); $i++) {
            $row = $rows[$i];

            $row->edit_url = "index.php?option=com_content&task=article.edit&id={$row->id}";

            // Get the article view url
            $url    = ContentHelperRoute::getArticleRoute($row->id . ':' . urlencode($row->alias), $row->catid);
            $url    = JRoute::_($url);
            $uri    = JUri::getInstance();
            $url    = $uri->toString(array('scheme', 'host', 'port')) . $url;
            $url    = str_replace('/administrator/', '/', $url);

            $row->view_url = $url;
        }

        return $rows;
    }

    /**
     * Get Pages
     *
     * @param int $lim0   Offset
     * @param int $lim    Limit
     * @param int $filter Filter
     *
     * @access  public
     *
     * @return array
     */
    public function getPages($lim0, $lim, $filter = null)
    {
        $db = JFactory::getDBO();

        $sql = "SELECT SQL_CALC_FOUND_ROWS c.id, c.title, c.state,
            if (c.fulltext != '', c.fulltext, c.introtext) AS content
            FROM #__content c
            LEFT JOIN #__categories cc ON cc.id=c.catid
            WHERE 1
            ";

        $search = JRequest::getVar("com_content_filter_search", "");
        $catId = JRequest::getVar("com_content_filter_catid", "0");
        $authorId = JRequest::getVar("com_content_filter_authorid", "0");
        $level = JRequest::getVar("com_content_filter_level", "0");
        $state = JRequest::getVar("com_content_filter_state", "");
        $access = JRequest::getVar("com_content_filter_access", "");

        $comContentFilterShowEmptyDescriptions = JRequest::getVar("com_content_filter_show_empty_descriptions", "-1");

        if ($search != "") {
            if (is_numeric($search)) {
                $sql .= " AND c.id=" . $db->quote($search);
            } else {
                $sql .= " AND c.title LIKE " . $db->quote('%' . $search . '%');
            }
        }

        $baselevel = 1;

        if ($catId > 0) {
            $db->setQuery("SELECT * from #__categories where id=" . $db->quote($catId));
            $cat_tbl = $db->loadObject();
            $rgt = $cat_tbl->rgt;
            $lft = $cat_tbl->lft;
            $baselevel = (int) $cat_tbl->level;
            $sql .= ' AND cc.lft >= ' . (int) $lft;
            $sql .= ' AND cc.rgt <= ' . (int) $rgt;
        }

        if ($level > 0) {
            $sql .= ' AND cc.level <=' . ((int) $level + (int) $baselevel - 1);
        }

        if ($authorId > 0) {
            $sql .= " AND c.created_by=" . $db->quote($authorId);
        }

        switch ($state) {
            case 'P':
                $sql .= " AND c.state=1";
                break;

            case 'U':
                $sql .= " AND c.state=0";
                break;

            case 'A':
                $sql .= " AND c.state=-1";
                break;

            case 'D':
                $sql .= " AND c.state=-2";
                break;

            case 'All':
                break;

            default:
                $sql .= " AND c.state=1";
                break;
        }

        if ($comContentFilterShowEmptyDescriptions != "-1") {
            $sql .= " AND (ISNULL(c.metadesc) OR c.metadesc='') ";
        }

        if (!empty($access)) {
            $sql .= " AND c.access = " . $db->quote($access);
        }

        $db->setQuery($sql, $lim0, $lim);
        $rows = $db->loadObjectList();

        if ($db->getErrorNum()) {
            echo $db->stderr();

            return false;
        }

        // Get outgoing links
        for ($i = 0; $i < count($rows); $i++) {
            $rows[$i]->edit_url = "index.php?option=com_content&task=article.edit&id={$rows[$i]->id}";
        }

        return $rows;
    }

    /**
     * Save meta tags
     *
     * @param array $ids              IDs
     * @param array $metatitles       Meta titles
     * @param array $metadescriptions Meta Descriptions
     * @param array $aliases          Aliases
     *
     * @access  public
     *
     * @return void
     */
    public function saveMetatags($ids, $metatitles, $metadescriptions, $aliases)
    {
        $db = JFactory::getDBO();

        for ($i = 0; $i < count($ids); $i++) {
            // Get current article metadata
            $sql = "SELECT metadata FROM #__content"
                . " WHERE id=" . $db->quote($ids[$i]);
            $db->setQuery($sql);
            $result = $db->loadObject();

            // Update the metadata
            $metadata = json_decode($result->metadata);
            if (!is_object($metadata)) {
                $metadata = new stdClass;
            }
            $metadata->metatitle = $metatitles[$i];
            $metadata = json_encode($metadata);

            $sql = "UPDATE #__content SET "
                . " metadesc=" . $db->quote($metadescriptions[$i]) . ", "
                . " metadata=" . $db->quote($metadata);

            if (isset($aliases[$i])) {
                $sql .= ", alias=" . $db->quote($aliases[$i]);
            }

            $sql .= " WHERE id=" . $db->quote($ids[$i]);
            $db->setQuery($sql);
            $db->query();

            // Insert/Update OS Metadata
            $sql = "INSERT INTO #__osmeta_metadata (item_id,
                item_type, title, description)
                VALUES (
                " . $db->quote($ids[$i]) . ",
                1,
                " . $db->quote($metatitles[$i]) . ",
                " . $db->quote($metadescriptions[$i]) . "
                ) ON DUPLICATE KEY UPDATE title=" . $db->quote($metatitles[$i]) . " ,
                    description=" . $db->quote($metadescriptions[$i]);

            $db->setQuery($sql);
            $db->query();
        }
    }

    /**
     * Method to copy the item title to title
     *
     * @param array $ids IDs list
     *
     * @access  public
     *
     * @return void
     */
    public function copyItemTitleToSearchEngineTitle($ids)
    {
        $db = JFactory::getDBO();

        foreach ($ids as $key => $value) {
            if (!is_numeric($value)) {
                unset($ids[$key]);
            }
        }

        $sql = "SELECT id, title FROM  #__content WHERE id IN (" . implode(",", $ids) . ")";
        $db->setQuery($sql);
        $items = $db->loadObjectList();

        foreach ($items as $item) {
            if ($item->title != '') {
                $sql = "INSERT INTO #__osmeta_metadata (item_id,
                    item_type, title, description)
                    VALUES (
                    " . $db->quote($item->id) . ",
                    1,
                    " . $db->quote($item->title) . ",
                    ''
                    ) ON DUPLICATE KEY UPDATE title=" . $db->quote($item->title);

                $db->setQuery($sql);
                $db->query();
            }
        }
    }

    /**
     * Method to generate descriptions
     *
     * @param array $ids IDs list
     *
     * @access  public
     *
     * @return void
     */
    public function generateDescriptions($ids)
    {
        $max_description_length = 500;
        $model = JModelLegacy::getInstance("options", "OSModel");
        $params = $model->getOptions();
        $max_description_length = $params->max_description_length ?
            $params->max_description_length : $max_description_length;

        $db = JFactory::getDBO();

        foreach ($ids as $key => $value) {
            if (!is_numeric($value)) {
                unset($ids[$key]);
            }
        }

        $sql = "SELECT id, introtext FROM  #__content WHERE id IN (" . implode(",", $ids) . ")";
        $db->setQuery($sql);
        $items = $db->loadObjectList();

        foreach ($items as $item) {
            if ($item->introtext != '') {
                $introtext = strip_tags($item->introtext);

                if (strlen($introtext) > $max_description_length) {
                    $introtext = substr($introtext, 0, $max_description_length);
                }

                $sql = "INSERT INTO #__osmeta_metadata (item_id,
                    item_type, title, description)
                    VALUES (
                    " . $db->quote($item->id) . ",
                    1,

                    '',
                    " . $db->quote($introtext) . "
                    ) ON DUPLICATE KEY UPDATE description=" . $db->quote($introtext);

                $db->setQuery($sql);
                $db->query();

                $sql = "UPDATE #__content SET metadesc=" . $db->quote($introtext) . "
                    WHERE id=" . $db->quote($item->id);

                $db->setQuery($sql);
                $db->query();
            }
        }
    }

    /**
     * Method to get Filter
     *
     * @access  public
     *
     * @return string
     */
    public function getFilter()
    {
        $db = JFactory::getDBO();
        $search = JRequest::getVar("com_content_filter_search", "");
        $catId = JRequest::getVar("com_content_filter_catid", "0");
        $level = JRequest::getVar("com_content_filter_level", "0");
        $access = JRequest::getVar("com_content_filter_access", "");

        // Levels filter.
        $levels = array();
        $levels[]   = JHtml::_('select.option', '1', JText::_('J1'));
        $levels[]   = JHtml::_('select.option', '2', JText::_('J2'));
        $levels[]   = JHtml::_('select.option', '3', JText::_('J3'));
        $levels[]   = JHtml::_('select.option', '4', JText::_('J4'));
        $levels[]   = JHtml::_('select.option', '5', JText::_('J5'));
        $levels[]   = JHtml::_('select.option', '6', JText::_('J6'));
        $levels[]   = JHtml::_('select.option', '7', JText::_('J7'));
        $levels[]   = JHtml::_('select.option', '8', JText::_('J8'));
        $levels[]   = JHtml::_('select.option', '9', JText::_('J9'));
        $levels[]   = JHtml::_('select.option', '10', JText::_('J10'));

        $authorId = JRequest::getVar("com_content_filter_authorid", "0");
        $state = JRequest::getVar("com_content_filter_state", "");
        $comContentFilterShowEmptyDescriptions = JRequest::getVar("com_content_filter_show_empty_descriptions", "-1");

        $result = 'Filter:
            <input type="text" name="com_content_filter_search" id="search" value="' . $search
            . '" class="text_area" onchange="document.adminForm.submit();" '
            . ' title="Filter by Title or enter an Article ID"/>
            <button id="Go" class="btn btn-small" onclick="this.form.submit();">Go</button>
            <button class="btn btn-small" onclick="document.getElementById(\'search\').value=\'\';
                this.form.getElementById(\'filter_sectionid\').value=\'-1\';
                this.form.getElementById(\'catid\').value=\'0\';
                this.form.getElementById(\'filter_authorid\').value=\'0\';
                this.form.getElementById(\'filter_state\').value=\'\';this.form.submit();">Reset</button>

            &nbsp;&nbsp;&nbsp;';

        $result .= '<select name="com_content_filter_catid" onchange="submitform();">' .
                        '<option value="">Select category</option>' .
        JHtml::_('select.options', JHtml::_('category.options', 'com_content'), 'value', 'text', $catId) .
                    '</select>';

        $result .= '<select name="com_content_filter_level" onchange="this.form.submit()">' .
                '<option value="">Select max levels</option>' .
                JHtml::_('select.options', $levels, 'value', 'text', $level) .
            '</select>';

        $descriptionChecked = $comContentFilterShowEmptyDescriptions != "-1" ? 'checked="yes" ' : '';

        $result .= '

            <select name="com_content_filter_state" id="filter_state" size="1"
            onchange="submitform();">
                <option value=""  >- Select State -</option>
                <option value="P" ' . ($state == 'P' ? 'selected="selected"' : '') . '>Published</option>
                <option value="U" ' . ($state == 'U' ? 'selected="selected"' : '') . '>Unpublished</option>
                <option value="A" ' . ($state == 'A' ? 'selected="selected"' : '') . '>Archived</option>
                <option value="D" ' . ($state == 'D' ? 'selected="selected"' : '') . '>Trashed</option>
                <option value="All" ' . ($state == 'All' ? 'selected="selected"' : '') . '>All</option>
            </select>
            <br/>
            <label>Show only Articles with empty descriptions</label>
            <input type="checkbox" onchange="document.adminForm.submit();"
                name="com_content_filter_show_empty_descriptions" ' . $descriptionChecked . '/>&nbsp;';

        $result .= JHtml::_('access.level', 'com_content_filter_access', $access, 'onchange="submitform();"');

        return $result;
    }

    /**
     * Method to set Metadata
     *
     * @param int   $id   ID
     * @param array $data Data
     *
     * @access  public
     *
     * @return void
     */
    public function setMetadata($id, $data)
    {
        $db = JFactory::getDBO();
        $sql = "UPDATE #__content SET " .
            (isset($data["title"])&&$data["title"]?
            "`title` = " . $db->quote($data["title"]) . ",":"") . "
            `metadesc` = " . $db->quote($data["metadescription"]) . "
            WHERE `id`=" . $db->quote($id);
        $db->setQuery($sql);
        $db->query();

        parent::setMetadata($id, $data);
    }

    /**
     * Method to get Metadata
     *
     * @param string $query Query
     *
     * @access  public
     *
     * @return array
     */
    public function getMetadataByRequest($query)
    {
        $params = array();
        parse_str($query, $params);
        $metadata = null;

        if (isset($params["id"])) {
            $metadata = $this->getMetadata($params["id"]);
        }

        return $metadata;
    }

    /**
     * Method to set Metadata by request
     *
     * @param string $url  URL
     * @param array  $data Data
     *
     * @access  public
     *
     * @return void
     */
    public function setMetadataByRequest($url, $data)
    {
        $params = array();
        parse_str($url, $params);

        if (isset($params["id"]) && $params["id"]) {
            $this->setMetadata($params["id"], $data);
        }
    }

    /**
     * Check if the component is available
     *
     * @return boolean
     */
    public static function isAvailable()
    {
        return true;
    }
}
