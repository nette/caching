<?php
%A%
		echo 'Noncached content

';
		if ($this->global->cache->createCache('%a%', [$id, 'tags' => 'mytag'])) /* line %a% */
		try {
			echo '
<h1>';
			echo LR\%a%(($this->filters->upper)($title)) /* line %a% */;
			echo '</h1>

';
			$this->createTemplate('include.cache.latte', ['localvar' => 11] + $this->params, 'include')->renderToContentType('html') /* line %a% */;
			echo "\n";

			$this->global->cache->end() /* line %a% */;
		} catch (\Throwable $ʟ_e) {
			$this->global->cache->rollback();
			throw $ʟ_e;
		}
	}


	public function prepare(): array
	{
%A%
		$this->global->cache->initialize($this);
%A%
