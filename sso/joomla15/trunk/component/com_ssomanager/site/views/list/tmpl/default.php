<h1>Forms</h1>
<ul>
	<?php 
	$fc = count($this->forms);
	for($i = 0; $i < $fc; $i++) {
		echo '<li>'. $this->forms[$i] .'</li>';
	}
	?>
</ul>
<h1>Links</h1>
<ul>
	<?php 
	$lc = count($this->links);
	for($i = 0; $i < $lc; $i++) {
		echo '<li>'. $this->links[$i] .'</li>';
	}
	?>
</ul>