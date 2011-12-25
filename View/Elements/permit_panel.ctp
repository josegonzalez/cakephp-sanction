<h2> <?php __d('sanction', 'Permit Component'); ?></h2>
<?php
echo '<h4>' . __d('sanction', 'Executed Rule', true) . '</h4>';
echo $this->Toolbar->makeNeatArray(array('rule' => $content['executed']));

echo '<h4>' . __d('sanction', 'Access Rules', true) . '</h4>';
echo $this->Toolbar->makeNeatArray($content['routes']);

echo '<h4>' . __d('sanction', 'Identified User', true) . '</h4>';
echo $this->Toolbar->makeNeatArray($content['user']);

?>