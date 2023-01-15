<?php
%A%
		echo 'Noncached content

';
		if ($this->global->cache->createCache('%a%', [$id, 'tags' => 'mytag'])) /* line %d% */
		try {
			echo '
<h1>';
			echo LR\Filters::escapeHtmlText(($this->filters->upper)($title)) /* line %d% */;
			echo '</h1>

';
			$this->createTemplate('include.cache.latte', ['localvar' => 11] + $this->params, 'include')->renderToContentType('html') /* line %d% */;
			echo "\n";

			$this->global->cache->end() /* line %d% */;
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
