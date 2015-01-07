<?php
/**
 * @package   OSMeta
 * @contact   www.alledia.com, support@alledia.com
 * @copyright 2013-2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU/GPL
 */

// No direct access
defined('_JEXEC') or die();

?>
<form action="index.php" method="post" name="adminForm" id="adminForm">
    <input type="hidden" name="filter_order" value="<?php echo $this->order ?>" />
    <input type="hidden" name="filter_order_Dir" value="<?php echo $this->order_Dir ?>" />
    <table width="100%">
        <tr>
            <td align="right">
                <label><?php echo JText::_('COM_OSMETA_SELECT_CONTENT_TYPE') ?>:&nbsp;</label>
                <select name="type" onchange="document.adminForm.submit();">
                    <?php
                    foreach ($this->availableTypes as $typeId => $typeName) :
                        $selected = $typeId == $this->itemType ? 'selected="true"' : '';
                        ?>
                        <option value="<?php echo $typeId?>" <?php echo $selected; ?>>
                            <?php echo $typeName["name"]?>
                        </option>
                        <?php
                    endforeach;
                    ?>
                </select>
            </td>
        </tr>
        <tr>
            <td align="right">
                <?php echo $this->filter; ?>
            </td>
        </tr>
    </table>
    <table class="table table-striped adminlist" id="articleList">
        <thead>
            <tr>
                <?php if (version_compare(JVERSION, '3.0', 'le')) : ?>
                    <th width="2%"><input type="checkbox" name="toggle" value=""
                        onclick="checkAll(<?php echo count($this->metatagsData); ?>);" />
                    </th>
                <?php else : ?>
                    <th width="2%"><input type="checkbox" name="checkall-toggle" value=""
                        title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
                    </th>
                <?php endif; ?>

                <th class="title title-column" width="<?php echo $this->extension->isPro() ? '20%' : '25%'; ?>">
                    <?php echo JHTML::_('grid.sort', JText::_('COM_OSMETA_TITLE_LABEL'), 'title', $this->order_Dir,
                        $this->order, "view"); ?>
                </th>


                <?php if ($this->extension->isPro()) : ?>
                    <?php echo Alledia\OSMeta\Pro\Fields::additionalFieldsHeader($this->order_Dir, $this->order); ?>
                <?php endif; ?>

                <th class="title" width="<?php echo $this->extension->isPro() ? '24%' : '35%'; ?>">
                    <?php echo JHTML::_('grid.sort', JText::_('COM_OSMETA_SEARCH_ENGINE_TITLE_LABEL'), 'meta_title',
                        $this->order_Dir, $this->order, "view"); ?>
                </th>

                <th class="title" width="<?php echo $this->extension->isPro() ? '24%' : '35%'; ?>">
                    <?php echo JHTML::_('grid.sort', JText::_('COM_OSMETA_DESCRIPTION_LABEL'), 'meta_desc',
                        $this->order_Dir, $this->order, "view"); ?>
                </th>
            </tr>

        </thead>
        <tr>
            <td width="20"></td>
            <td class="title"></td>

            <?php if ($this->extension->isPro()) : ?>
                <td></td>
            <?php endif; ?>

            <td valign="top">
                <?php echo JText::_('COM_OSMETA_SEARCH_ENGINE_TITLE_DESC') ?>
            </td>
            <td valign="top">
                <?php echo JText::_('COM_OSMETA_DESCRIPTION_DESC') ?>
            </td>
        </tr>

        <tr class="subheader">
            <td colspan="<?php echo $this->extension->isPro() ? '5' : '4'; ?>"><?php echo JText::_('COM_OSMETA_HOMEPAGE_METADATA'); ?></td>
        </tr>
        <tr id="homeMetaDataRow" class="row0">
            <td></td>
            <td>
                <input type="radio" name="home_metadata_source" id="home_metadata_source_default" value="default"
                    <?php echo $this->homeMetatagsData->source === 'default' ? 'checked="checked"' : ''; ?> />
                <label for="home_metadata_source_default" title="<?php echo JText::_('COM_OSMETA_FEATURED_DEFAULT_VALUES'); ?>">
                    <?php echo JText::_('COM_OSMETA_DEFAULT_VALUES'); ?>
                </label>

                <br />
                <input type="radio" name="home_metadata_source" id="home_metadata_source_custom" value="custom"
                    <?php echo $this->homeMetatagsData->source === 'custom' ? 'checked="checked"' : ''; ?> />
                <label for="home_metadata_source_custom" title="<?php echo JText::_('COM_OSMETA_FEATURED_CUSTOM_VALUES'); ?>">
                    <?php echo JText::_('COM_OSMETA_CUSTOM_VALUES'); ?>
                </label>

                <br />
                <input type="radio" name="home_metadata_source" id="home_metadata_source_featured" value="featured"
                    <?php echo $this->homeMetatagsData->source === 'featured' ? 'checked="checked"' : ''; ?> />
                <label for="home_metadata_source_featured" title="<?php echo JText::_('COM_OSMETA_FEATURED_VALUES_TITLE'); ?>">
                    <?php echo JText::_('COM_OSMETA_FEATURED_VALUES'); ?>
                </label>
            </td>

            <?php if ($this->extension->isPro()) : ?>
                <td></td>
            <?php endif; ?>

            <td class="field-column">
                <input type="text" name="home_metatitle" <?php echo $this->homeFieldsDisabledAttribute; ?> value="<?php echo $this->homeMetatagsData->metaTitle; ?>" class="char-count">
            </td>
            <td class="field-column">
                <textarea name="home_metadesc" <?php echo $this->homeFieldsDisabledAttribute; ?> class="char-count"><?php echo $this->homeMetatagsData->metaDesc; ?></textarea>
            </td>
        </tr>

        <tr class="subheader">
            <td colspan="<?php echo $this->extension->isPro() ? '5' : '4'; ?>" rowspan="" headers=""><?php echo JText::_($this->itemTypeShort); ?> <?php echo JText::_('COM_OSMETA_METADATA'); ?></td>
        </tr>

        <?php
        jimport('joomla.filter.output');

        $k = 0;
        for ($i = 0, $n = count($this->metatagsData); $i < $n; $i++) {
            $row = $this->metatagsData[$i];
            $checked = JHTML::_('grid.id', $i, $row->id);
            ?>
            <tr class="<?php echo "row$k"; ?>">
                <td><?php echo $checked; ?>
                    <input type="hidden" name="ids[]" value="<?php echo $row->id ?>"/>
                </td>
                <td>
                    <a id="title_<?php echo $row->id ?>" href="<?php echo $row->edit_url; ?>">
                        <?php echo $row->title; ?>
                    </a>
                    <a class="external-link" href="<?php echo $row->view_url; ?>" target="_blank">
                        <?php if (version_compare(JVERSION, '3.0', 'lt')) : ?>
                            <img src="../media/com_osmeta/images/external-link.png" width="14" height="14" />
                        <?php else : ?>
                            <span class="icon-out-2"></span>
                        <?php endif; ?>
                    </a>
                </td>

                <?php if ($this->extension->isPro()) : ?>
                    <?php echo Alledia\OSMeta\Pro\Fields::additionalFields($row); ?>
                <?php endif; ?>

                <td class="field-column">
                    <input type="text" name="metatitle[]" value="<?php echo $row->metatitle; ?>" class="char-count">
                </td>
                <td class="field-column">
                    <textarea name="metadesc[]" class="char-count"><?php echo $row->metadesc; ?></textarea>
                </td>
            </tr>
            <?php
            $k = 1 - $k;
        }
        ?>
        <tfoot>
            <tr>
                <td colspan="<?php echo $this->extension->isPro() ? '5' : '4'; ?>"><?php echo $this->pageNav->getListFooter(); ?></td>
            </tr>
        </tfoot>
    </table>
    <input type="hidden" name="option" value="com_osmeta" />
    <input type="hidden" name="task" value="view" />
    <input type="hidden" name="boxchecked" value="0" />
</form>

<div id="footer">
    <div>
        <a href="https://www.alledia.com">
            <img src="../media/com_osmeta/admin/images/alledia_logo_150x43.png" />
        </a>
    </div>
    <br />
    <div>
        OSMeta is built by&nbsp;
        <a href="https://www.alledia.com">Alledia</a>
    </div>
</div>

<?php if (version_compare(JVERSION, '3.0', 'lt')) : ?>
    <script src="../media/com_osmeta/js/jquery.js"></script>
<?php endif; ?>

<script src="../media/com_osmeta/js/jquery.osmetacharcount.min.js"></script>

<script>
    var hashCode = function(s) {
        return s.split("").reduce(function(a,b){a=((a<<5)-a)+b.charCodeAt(0);return a&a},0);
    }

    var hashedInitialValues = '',
        getHashedValues;

    (function($) {
        var homeMetadataSourceChange = function() {
            var $this = $(this);
            var fields = $('#homeMetaDataRow textarea, #homeMetaDataRow input[type="text"]');
            var value = $this.val();

            fields.attr('readonly', !(value === 'custom'));

        };

        $('#home_metadata_source_default, #home_metadata_source_custom, #home_metadata_source_featured').on(
            'change',
            homeMetadataSourceChange
        );

        // Get a hash from the value of all fields, concatenated
        getHashedValues = function() {
            var str = ''

            $('#articleList input, #articleList textarea').each(function() {
                str += $(this).val();
            });

            return hashCode(str);
        };

        $('#articleList input[type="text"].char-count').osmetaCharCount({
            limit: 70,
            message: '<?php echo JText::_("COM_OSMETA_TITLE_TOO_LONG"); ?>',
            charStr: '<?php echo JText::_("COM_OSMETA_CHAR"); ?>',
            charPluralStr: '<?php echo JText::_("COM_OSMETA_CHARS"); ?>'
        });

        $('#articleList textarea.char-count').osmetaCharCount({
            limit: 160,
            message: '<?php echo JText::_("COM_OSMETA_DESCR_TOO_LONG"); ?>',
            charStr: '<?php echo JText::_("COM_OSMETA_CHAR"); ?>',
            charPluralStr: '<?php echo JText::_("COM_OSMETA_CHARS"); ?>'
        });
    })(jQuery);

    // Store the initial hash
    hashedInitialValues = getHashedValues();

    // Overwrite the native submit action, to catch the cancel task
    var nativeSubmitButton = Joomla.submitbutton;
    Joomla.submitbutton = function(pressbutton) {
        if (pressbutton === 'cancel') {
            var hashedValues = getHashedValues();

            // Do we have any modified field?
            if (hashedInitialValues !== hashedValues) {
                if (!confirm('<?php echo JText::_("COM_OSMETA_CONFIRM_CANCEL"); ?>')) {
                    return;
                }
            }
        }

        nativeSubmitButton(pressbutton);
    };
</script>
