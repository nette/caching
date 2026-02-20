<?php
%A%
		if ($this->global->cache->createCache('%a%')) /* pos %a% */
		try {
			echo '	';
			echo LR\%a%(($this->filters->lower)($title)) /* pos %a% */;
			echo "\n";

			$this->global->cache->end() /* pos %a% */;
		} catch (\Throwable $ʟ_e) {
			$this->global->cache->rollback();
			throw $ʟ_e;
		}
%A%
