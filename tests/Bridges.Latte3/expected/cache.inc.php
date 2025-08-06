<?php
%A%
		if ($this->global->cache->createCache('%a%')) /* line %a% */
		try {
			echo '	';
			echo LR\%a%(($this->filters->lower)($title)) /* line %a% */;
			echo "\n";

			$this->global->cache->end() /* line %a% */;
		} catch (\Throwable $ʟ_e) {
			$this->global->cache->rollback();
			throw $ʟ_e;
		}
%A%
