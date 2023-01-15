<?php
%A%
		if ($this->global->cache->createCache('%a%')) /* line %d% */
		try {
			echo '	';
			echo LR\Filters::escapeHtmlText(($this->filters->lower)($title)) /* line %d% */;
			echo "\n";

			$this->global->cache->end() /* line %d% */;
		} catch (\Throwable $ʟ_e) {
			$this->global->cache->rollback();
			throw $ʟ_e;
		}
%A%
