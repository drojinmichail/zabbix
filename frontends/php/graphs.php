<?php
/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/config.inc.php';
require_once dirname(__FILE__).'/include/hosts.inc.php';
require_once dirname(__FILE__).'/include/graphs.inc.php';
require_once dirname(__FILE__).'/include/forms.inc.php';

$page['title'] = isset($_REQUEST['parent_discoveryid']) ? _('Configuration of graph prototypes') : _('Configuration of graphs');
$page['file'] = 'graphs.php';
$page['hist_arg'] = array('hostid', 'parent_discoveryid');

require_once dirname(__FILE__).'/include/page_header.php';

// VAR	TYPE	OPTIONAL	FLAGS	VALIDATION	EXCEPTION
$fields = array(
	'parent_discoveryid' =>	array(T_ZBX_INT, O_OPT, P_SYS,		DB_ID,			null),
	'groupid' =>			array(T_ZBX_INT, O_OPT, P_SYS,		DB_ID,			null),
	'hostid' =>				array(T_ZBX_INT, O_OPT, P_SYS,		DB_ID,			null),
	'copy_type' => array(T_ZBX_INT, O_OPT, P_SYS, IN(array(COPY_TYPE_TO_HOST, COPY_TYPE_TO_HOST_GROUP, COPY_TYPE_TO_TEMPLATE)), 'isset({copy})'),
	'copy_mode' =>			array(T_ZBX_INT, O_OPT, P_SYS,		IN('0'),		null),
	'graphid' =>			array(T_ZBX_INT, O_OPT, P_SYS,		DB_ID,			'isset({form})&&{form}=="update"'),
	'name' =>				array(T_ZBX_STR, O_OPT, null,		NOT_EMPTY,		'isset({save})', _('Name')),
	'width' =>				array(T_ZBX_INT, O_OPT, null,		BETWEEN(20, 65535), 'isset({save})', _('Width').' (min:20, max:65535)'),
	'height' =>				array(T_ZBX_INT, O_OPT, null,		BETWEEN(20, 65535), 'isset({save})', _('Height').' (min:20, max:65535)'),
	'graphtype' =>			array(T_ZBX_INT, O_OPT, null,		IN('0,1,2,3'),	'isset({save})'),
	'show_3d' =>			array(T_ZBX_INT, O_OPT, P_NZERO,	IN('0,1'),		null),
	'show_legend' =>		array(T_ZBX_INT, O_OPT, P_NZERO,	IN('0,1'),		null),
	'ymin_type' =>			array(T_ZBX_INT, O_OPT, null,		IN('0,1,2'),	null),
	'ymax_type' =>			array(T_ZBX_INT, O_OPT, null,		IN('0,1,2'),	null),
	'yaxismin' =>			array(T_ZBX_DBL, O_OPT, null,		null,			'isset({save})&&({graphtype}==0||{graphtype}==1)'),
	'yaxismax' =>			array(T_ZBX_DBL, O_OPT, null,		null,			'isset({save})&&({graphtype}==0||{graphtype}==1)'),
	'ymin_itemid' =>		array(T_ZBX_INT, O_OPT, null,		DB_ID,			'isset({save})&&isset({ymin_type})&&{ymin_type}==3'),
	'ymax_itemid' =>		array(T_ZBX_INT, O_OPT, null,		DB_ID,			'isset({save})&&isset({ymax_type})&&{ymax_type}==3'),
	'percent_left' =>		array(T_ZBX_DBL, O_OPT, null,		BETWEEN(0, 100), null, _('Percentile line (left)')),
	'percent_right' =>		array(T_ZBX_DBL, O_OPT, null,		BETWEEN(0, 100), null, _('Percentile line (right)')),
	'visible' =>			array(T_ZBX_INT, O_OPT, null,		BETWEEN(0, 1),	null),
	'items' =>				array(T_ZBX_STR, O_OPT, null,		null,			null),
	'show_work_period' =>	array(T_ZBX_INT, O_OPT, null,		IN('1'),		null),
	'show_triggers' =>		array(T_ZBX_INT, O_OPT, null,		IN('1'),		null),
	'group_graphid' =>		array(T_ZBX_INT, O_OPT, null,		DB_ID,			null),
	'copy_targetid' =>		array(T_ZBX_INT, O_OPT, null,		DB_ID,			null),
	'copy_groupid' =>		array(T_ZBX_INT, O_OPT, P_SYS,		DB_ID,			'isset({copy})&&isset({copy_type})&&{copy_type}==0'),
	// actions
	'go' =>					array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,			null),
	'save' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,			null),
	'clone' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,			null),
	'copy' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,			null),
	'delete' =>				array(T_ZBX_STR, O_OPT, P_SYS|P_ACT, null,			null),
	'cancel' =>				array(T_ZBX_STR, O_OPT, P_SYS,		null,			null),
	'form' =>				array(T_ZBX_STR, O_OPT, P_SYS,		null,			null),
	'form_refresh' =>		array(T_ZBX_INT, O_OPT, null,		null,			null)
);
$percentVisible = getRequest('visible');
if (!isset($percentVisible['percent_left'])) {
	unset($_REQUEST['percent_left']);
}
if (!isset($percentVisible['percent_right'])) {
	unset($_REQUEST['percent_right']);
}
if (isset($_REQUEST['yaxismin']) && zbx_empty($_REQUEST['yaxismin'])) {
	unset($_REQUEST['yaxismin']);
}
if (isset($_REQUEST['yaxismax']) && zbx_empty($_REQUEST['yaxismax'])) {
	unset($_REQUEST['yaxismax']);
}
check_fields($fields);
validate_sort_and_sortorder('name', ZBX_SORT_UP, array('name', 'graphtype'));

$_REQUEST['go'] = getRequest('go', 'none');
$_REQUEST['items'] = getRequest('items', array());
$_REQUEST['show_3d'] = getRequest('show_3d', 0);
$_REQUEST['show_legend'] = getRequest('show_legend', 0);

/*
 * Permissions
 */
if (CUser::$userData['type'] !== USER_TYPE_SUPER_ADMIN) {
	if (!empty($_REQUEST['parent_discoveryid'])) {
		// check whether discovery rule is editable by user
		$discovery_rule = API::DiscoveryRule()->get(array(
			'itemids' => array($_REQUEST['parent_discoveryid']),
			'output' => API_OUTPUT_EXTEND,
			'editable' => true,
			'preservekeys' => true
		));
		$discovery_rule = reset($discovery_rule);
		if (!$discovery_rule) {
			access_deny();
		}

		// sets corresponding hostid for later usage
		if (empty($_REQUEST['hostid'])) {
			$_REQUEST['hostid'] = $discovery_rule['hostid'];
		}

		// check whether graph prototype is editable by user
		if (isset($_REQUEST['graphid'])) {
			$graphPrototype = API::GraphPrototype()->get(array(
				'graphids' => array($_REQUEST['graphid']),
				'output' => array('graphid'),
				'editable' => true,
				'preservekeys' => true
			));
			if (empty($graphPrototype)) {
				access_deny();
			}
		}
	}
	elseif (!empty($_REQUEST['graphid'])) {
		// check whether graph is normal and editable by user
		$graphs = API::Graph()->get(array(
			'output' => array('graphid'),
			'filter' => array('flags' => ZBX_FLAG_DISCOVERY_NORMAL),
			'graphids' => array($_REQUEST['graphid']),
			'editable' => true,
			'preservekeys' => true
		));
		if (empty($graphs)) {
			access_deny();
		}
	}
	elseif (!empty($_REQUEST['hostid'])) {
		// check whether host is editable by user
		$hosts = API::Host()->get(array(
			'output' => array('hostid'),
			'hostids' => array($_REQUEST['hostid']),
			'templated_hosts' => true,
			'editable' => true,
			'preservekeys' => true
		));
		if (empty($hosts)) {
			access_deny();
		}
	}
}

/*
 * Actions
 */
if (isset($_REQUEST['clone']) && isset($_REQUEST['graphid'])) {
	// graph
	$options = array(
		'graphids' => $_REQUEST['graphid'],
		'output' => API_OUTPUT_EXTEND
	);
	$graph = empty($_REQUEST['parent_discoveryid'])
		? API::Graph()->get($options)
		: API::GraphPrototype()->get($options);
	$graph = reset($graph);

	$graph['items'] = API::GraphItem()->get(array(
		'graphids' => $_REQUEST['graphid'],
		'sortfield' => 'gitemid',
		'output' => API_OUTPUT_EXTEND,
		'expandData' => true
	));

	if($graph['templateid']) {
		$_REQUEST = array_merge($_REQUEST, $graph);
	}
	else {
		$graph = array_merge($graph, $_REQUEST);
	}

	unset($_REQUEST['graphid']);

	$_REQUEST['form'] = 'clone';
}
elseif (hasRequest('save')) {
	$items = getRequest('items', array());

	// remove passing "gitemid" to API if new items added via pop-up
	foreach ($items as &$item) {
		if (!$item['gitemid']) {
			unset($item['gitemid']);
		}
	}
	unset($item);

	$graph = array(
		'name' => getRequest('name'),
		'width' => getRequest('width'),
		'height' => getRequest('height'),
		'ymin_type' => getRequest('ymin_type', 0),
		'ymax_type' => getRequest('ymax_type', 0),
		'yaxismin' => getRequest('yaxismin', 0),
		'yaxismax' => getRequest('yaxismax', 0),
		'ymin_itemid' => getRequest('ymin_itemid'),
		'ymax_itemid' => getRequest('ymax_itemid'),
		'show_work_period' => getRequest('show_work_period', 0),
		'show_triggers' => getRequest('show_triggers', 0),
		'graphtype' => getRequest('graphtype'),
		'show_legend' => getRequest('show_legend', 1),
		'show_3d' => getRequest('show_3d', 0),
		'percent_left' => getRequest('percent_left', 0),
		'percent_right' => getRequest('percent_right', 0),
		'gitems' => $items
	);

	DBstart();

	// create and update graph prototypes
	if (hasRequest('parent_discoveryid')) {
		$graph['flags'] = ZBX_FLAG_DISCOVERY_PROTOTYPE;

		if (hasRequest('graphid')) {
			$graph['graphid'] = getRequest('graphid');
			$result = API::GraphPrototype()->update($graph);

			$messageSuccess = _('Graph prototype updated');
			$messageFailed = _('Cannot update graph prototype');
		}
		else {
			$result = API::GraphPrototype()->create($graph);

			$messageSuccess = _('Graph prototype added');
			$messageFailed = _('Cannot add graph prototype');
		}

		$cookieId = getRequest('parent_discoveryid');
	}
	// create and update graphs
	else {
		if (hasRequest('graphid')) {
			$graph['graphid'] = getRequest('graphid');
			$result = API::Graph()->update($graph);

			$messageSuccess = _('Graph updated');
			$messageFailed = _('Cannot update graph');
		}
		else {
			$result = API::Graph()->create($graph);

			$messageSuccess = _('Graph added');
			$messageFailed = _('Cannot add graph');
		}

		$cookieId = getRequest('hostid');
	}

	if ($result) {
		if (hasRequest('graphid')) {
			add_audit(AUDIT_ACTION_UPDATE, AUDIT_RESOURCE_GRAPH,
				'Graph ID ['.$graph['graphid'].'] Graph ['.getRequest('name').']'
			);
		}
		else {
			add_audit(AUDIT_ACTION_ADD, AUDIT_RESOURCE_GRAPH, 'Graph ['.getRequest('name').']');
		}

		unset($_REQUEST['form']);
	}

	$result = DBend($result);

	if ($result) {
		uncheckTableRows($cookieId);
	}
	show_messages($result, $messageSuccess, $messageFailed);
}
elseif (hasRequest('delete') && hasRequest('graphid')) {
	$graphId = getRequest('graphid');

	if (hasRequest('parent_discoveryid')) {
		$result = API::GraphPrototype()->delete(array($graphId));

		if ($result) {
			uncheckTableRows(getRequest('parent_discoveryid'));
		}
		show_messages($result, _('Graph prototype deleted'), _('Cannot delete graph prototype'));
	}
	else {
		$result = API::Graph()->delete(array($graphId));

		if ($result) {
			uncheckTableRows(getRequest('hostid'));
		}
		show_messages($result, _('Graph deleted'), _('Cannot delete graph'));
	}

	if ($result) {
		unset($_REQUEST['form']);
	}
}
elseif (getRequest('go') == 'delete' && hasRequest('group_graphid')) {
	$graphIds = getRequest('group_graphid');

	if (hasRequest('parent_discoveryid')) {
		$result = API::GraphPrototype()->delete($graphIds);

		if ($result) {
			uncheckTableRows(getRequest('parent_discoveryid'));
		}
		show_messages($result, _('Graph prototypes deleted'), _('Cannot delete graph prototypes'));
	}
	else {
		$result = API::Graph()->delete($graphIds);

		if ($result) {
			uncheckTableRows(getRequest('hostid'));
		}
		show_messages($result, _('Graphs deleted'), _('Cannot delete graphs'));
	}
} elseif (getRequest('go') == 'copy_to' && hasRequest('copy') && hasRequest('group_graphid')) {
	if (getRequest('copy_targetid') != 0 && hasRequest('copy_type')) {
		$result = true;

		$options = array(
			'output' => array('hostid'),
			'editable' => true,
			'templated_hosts' => true
		);

		// hosts or templates
		if (getRequest('copy_type') == COPY_TYPE_TO_HOST || getRequest('copy_type') == COPY_TYPE_TO_TEMPLATE) {
			$options['hostids'] = getRequest('copy_targetid');
		}
		// host groups
		else {
			zbx_value2array(getRequest('copy_targetid'));

			$dbGroups = API::HostGroup()->get(array(
				'output' => array('groupid'),
				'groupids' => getRequest('copy_targetid'),
				'editable' => true
			));
			$dbGroups = zbx_toHash($dbGroups, 'groupid');

			foreach (getRequest('copy_targetid') as $groupid) {
				if (!isset($dbGroups[$groupid])) {
					access_deny();
				}
			}

			$options['groupids'] = getRequest('copy_targetid');
		}

		$dbHosts = API::Host()->get($options);

		DBstart();
		foreach (getRequest('group_graphid') as $graphid) {
			foreach ($dbHosts as $host) {
				$result &= (bool) copyGraphToHost($graphid, $host['hostid']);
			}
		}
		$result = DBend($result);

		if ($result) {
			uncheckTableRows(
				getRequest('parent_discoveryid') == 0 ? getRequest('hostid') : getRequest('parent_discoveryid')
			);
		}
		show_messages($result, _('Graphs copied'), _('Cannot copy graphs'));

		$_REQUEST['go'] = 'none2';
	}
	else {
		error(_('No target selected.'));
	}
	show_messages();
}

/*
 * Display
 */
$pageFilter = new CPageFilter(array(
	'groups' => array(
		'with_hosts_and_templates' => true,
		'editable' => true
	),
	'hosts' => array(
		'editable' => true,
		'templated_hosts' => true
	),
	'groupid' => getRequest('groupid', null),
	'hostid' => getRequest('hostid', null)
));

if (empty($_REQUEST['parent_discoveryid'])) {
	if ($pageFilter->groupid > 0) {
		$_REQUEST['groupid'] = $pageFilter->groupid;
	}
	if ($pageFilter->hostid > 0) {
		$_REQUEST['hostid'] = $pageFilter->hostid;
	}
}

if ($_REQUEST['go'] == 'copy_to' && isset($_REQUEST['group_graphid'])) {
	// render view
	$graphView = new CView('configuration.copy.elements', getCopyElementsFormData('group_graphid'));
	$graphView->render();
	$graphView->show();
}
elseif (isset($_REQUEST['form'])) {
	$data = array(
		'form' => getRequest('form'),
		'form_refresh' => getRequest('form_refresh', 0),
		'graphid' => getRequest('graphid', 0),
		'parent_discoveryid' => getRequest('parent_discoveryid'),
		'group_gid' => getRequest('group_gid', array()),
		'hostid' => getRequest('hostid', 0),
		'normal_only' => getRequest('normal_only')
	);

	if (!empty($data['graphid']) && !isset($_REQUEST['form_refresh'])) {
		$options = array(
			'graphids' => $data['graphid'],
			'output' => API_OUTPUT_EXTEND,
			'selectHosts' => array('hostid')
		);
		$graph = empty($data['parent_discoveryid']) ? API::Graph()->get($options) : API::GraphPrototype()->get($options);
		$graph = reset($graph);

		$data['name'] = $graph['name'];
		$data['width'] = $graph['width'];
		$data['height'] = $graph['height'];
		$data['ymin_type'] = $graph['ymin_type'];
		$data['ymax_type'] = $graph['ymax_type'];
		$data['yaxismin'] = $graph['yaxismin'];
		$data['yaxismax'] = $graph['yaxismax'];
		$data['ymin_itemid'] = $graph['ymin_itemid'];
		$data['ymax_itemid'] = $graph['ymax_itemid'];
		$data['show_work_period'] = $graph['show_work_period'];
		$data['show_triggers'] = $graph['show_triggers'];
		$data['graphtype'] = $graph['graphtype'];
		$data['show_legend'] = $graph['show_legend'];
		$data['show_3d'] = $graph['show_3d'];
		$data['percent_left'] = $graph['percent_left'];
		$data['percent_right'] = $graph['percent_right'];
		$data['templateid'] = $graph['templateid'];
		$data['templates'] = array();

		// if no host has been selected for the navigation panel, use the first graph host
		if (empty($data['hostid'])) {
			$host = reset($graph['hosts']);
			$data['hostid'] = $host['hostid'];
		}

		// templates
		if (!empty($data['templateid'])) {
			$parentGraphid = $data['templateid'];
			do {
				$parentGraph = getGraphByGraphId($parentGraphid);

				// parent graph prototype link
				if (getRequest('parent_discoveryid')) {
					$parentGraphPrototype = API::GraphPrototype()->get(array(
						'output' => array('graphid'),
						'graphids' => $parentGraph['graphid'],
						'selectTemplates' => API_OUTPUT_EXTEND,
						'selectDiscoveryRule' => array('itemid')
					));
					if ($parentGraphPrototype) {
						$parentGraphPrototype = reset($parentGraphPrototype);
						$parentTemplate = reset($parentGraphPrototype['templates']);

						$link = new CLink($parentTemplate['name'],
							'graphs.php?form=update&graphid='.$parentGraphPrototype['graphid'].'&hostid='.$parentTemplate['templateid'].'&parent_discoveryid='.$parentGraphPrototype['discoveryRule']['itemid']
						);
					}
				}
				// parent graph link
				else {
					$parentTemplate = get_hosts_by_graphid($parentGraph['graphid']);
					$parentTemplate = DBfetch($parentTemplate);

					$link = new CLink($parentTemplate['name'],
						'graphs.php?form=update&graphid='.$parentGraph['graphid'].'&hostid='.$parentTemplate['hostid']
					);
				}
				if (isset($link)) {
					$data['templates'][] = $link;
					$data['templates'][] = SPACE.RARR.SPACE;
				}
				$parentGraphid = $parentGraph['templateid'];
			} while ($parentGraphid != 0);
			$data['templates'] = array_reverse($data['templates']);
			array_shift($data['templates']);
		}

		// items
		$data['items'] = API::GraphItem()->get(array(
			'output' => array(
				'gitemid', 'graphid', 'itemid', 'type', 'drawtype', 'yaxisside', 'calc_fnc', 'color', 'sortorder'
			),
			'graphids' => $data['graphid'],
			'sortfield' => 'gitemid'
		));
	}
	else {
		$data['name'] = getRequest('name', '');
		$data['graphtype'] = getRequest('graphtype', GRAPH_TYPE_NORMAL);

		if ($data['graphtype'] == GRAPH_TYPE_PIE || $data['graphtype'] == GRAPH_TYPE_EXPLODED) {
			$data['width'] = getRequest('width', 400);
			$data['height'] = getRequest('height', 300);
		}
		else {
			$data['width'] = getRequest('width', 900);
			$data['height'] = getRequest('height', 200);
		}

		$data['ymin_type'] = getRequest('ymin_type', GRAPH_YAXIS_TYPE_CALCULATED);
		$data['ymax_type'] = getRequest('ymax_type', GRAPH_YAXIS_TYPE_CALCULATED);
		$data['yaxismin'] = getRequest('yaxismin', 0);
		$data['yaxismax'] = getRequest('yaxismax', 100);
		$data['ymin_itemid'] = getRequest('ymin_itemid', 0);
		$data['ymax_itemid'] = getRequest('ymax_itemid', 0);
		$data['show_work_period'] = getRequest('show_work_period', 0);
		$data['show_triggers'] = getRequest('show_triggers', 0);
		$data['show_legend'] = getRequest('show_legend', 0);
		$data['show_3d'] = getRequest('show_3d', 0);
		$data['visible'] = getRequest('visible');
		$data['percent_left'] = 0;
		$data['percent_right'] = 0;
		$data['visible'] = getRequest('visible');
		$data['items'] = getRequest('items', array());

		if (isset($data['visible']['percent_left'])) {
			$data['percent_left'] = getRequest('percent_left', 0);
		}
		if (isset($data['visible']['percent_right'])) {
			$data['percent_right'] = getRequest('percent_right', 0);
		}
	}

	if (empty($data['graphid']) && !isset($_REQUEST['form_refresh'])) {
		$data['show_legend'] = $_REQUEST['show_legend'] = 1;
		$data['show_work_period'] = $_REQUEST['show_work_period'] = 1;
		$data['show_triggers'] = $_REQUEST['show_triggers'] = 1;
	}

	// items
	if ($data['items']) {
		$items = API::Item()->get(array(
			'output' => array('itemid', 'hostid', 'name', 'key_', 'flags'),
			'selectHosts' => array('hostid', 'name'),
			'itemids' => zbx_objectValues($data['items'], 'itemid'),
			'filter' => array(
				'flags' => array(ZBX_FLAG_DISCOVERY_NORMAL, ZBX_FLAG_DISCOVERY_PROTOTYPE, ZBX_FLAG_DISCOVERY_CREATED)
			),
			'webitems' => true,
			'preservekeys' => true
		));

		foreach ($data['items'] as &$item) {
			$host = reset($items[$item['itemid']]['hosts']);

			$item['host'] = $host['name'];
			$item['hostid'] = $items[$item['itemid']]['hostid'];
			$item['name'] = $items[$item['itemid']]['name'];
			$item['key_'] = $items[$item['itemid']]['key_'];
			$item['flags'] = $items[$item['itemid']]['flags'];
		}
		unset($item);

		$data['items'] = CMacrosResolverHelper::resolveItemNames($data['items']);
	}

	$data['items'] = array_values($data['items']);
	$itemCount = count($data['items']);
	for ($i = 0; $i < $itemCount - 1;) {
		// check if we delete an item
		$next = $i + 1;
		while (!isset($data['items'][$next]) && $next < ($itemCount - 1)) {
			$next++;
		}

		if (isset($data['items'][$next]) && $data['items'][$i]['sortorder'] == $data['items'][$next]['sortorder']) {
			for ($j = $next; $j < $itemCount; $j++) {
				if ($data['items'][$j - 1]['sortorder'] >= $data['items'][$j]['sortorder']) {
					$data['items'][$j]['sortorder']++;
				}
			}
		}

		$i = $next;
	}
	asort_by_key($data['items'], 'sortorder');
	$data['items'] = array_values($data['items']);

	// is template
	$data['is_template'] = isTemplate($data['hostid']);

	// render view
	$graphView = new CView('configuration.graph.edit', $data);
	$graphView->render();
	$graphView->show();
}
else {
	$data = array(
		'pageFilter' => $pageFilter,
		'hostid' => ($pageFilter->hostid > 0) ? $pageFilter->hostid : getRequest('hostid'),
		'parent_discoveryid' => getRequest('parent_discoveryid'),
		'graphs' => array(),
		'discovery_rule' => empty($_REQUEST['parent_discoveryid']) ? null : $discovery_rule
	);

	$sortfield = getPageSortField('name');
	$sortorder = getPageSortOrder();

	// get graphs
	$options = array(
		'hostids' => $data['hostid'] ? $data['hostid'] : null,
		'groupids' => (!$data['hostid'] && $pageFilter->groupid > 0) ? $pageFilter->groupid : null,
		'discoveryids' => empty($_REQUEST['parent_discoveryid']) ? null : getRequest('parent_discoveryid'),
		'editable' => true,
		'output' => array('graphid', 'name', 'graphtype'),
		'limit' => $config['search_limit'] + 1
	);

	$data['graphs'] = empty($_REQUEST['parent_discoveryid'])
		? API::Graph()->get($options)
		: API::GraphPrototype()->get($options);

	if ($sortfield == 'graphtype') {
		foreach ($data['graphs'] as $gnum => $graph) {
			$data['graphs'][$gnum]['graphtype'] = graphType($graph['graphtype']);
		}
	}

	order_result($data['graphs'], $sortfield, $sortorder);

	$data['paging'] = getPagingLine($data['graphs']);

	// get graphs after paging
	$options = array(
		'graphids' => zbx_objectValues($data['graphs'], 'graphid'),
		'output' => array('graphid', 'name', 'templateid', 'graphtype', 'width', 'height'),
		'selectDiscoveryRule' => array('itemid', 'name'),
		'selectHosts' => $data['hostid'] ? null : array('name'),
		'selectTemplates' => $data['hostid'] ? null : array('name')
	);

	$data['graphs'] = empty($_REQUEST['parent_discoveryid'])
		? API::Graph()->get($options)
		: API::GraphPrototype()->get($options);

	foreach ($data['graphs'] as $gnum => $graph) {
		$data['graphs'][$gnum]['graphtype'] = graphType($graph['graphtype']);
	}

	order_result($data['graphs'], $sortfield, $sortorder);

	// render view
	$graphView = new CView('configuration.graph.list', $data);
	$graphView->render();
	$graphView->show();
}

require_once dirname(__FILE__).'/include/page_footer.php';
