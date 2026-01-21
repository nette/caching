<?php
%A%
		if ($this->global->cache->createCache('%a%')) /* %a% */
		try {
			echo '	';
			echo LR\%a%(($this->filters->lower)($title)) /* %a% */;
			echo "\n";

			$this->global->cache->end() /* %a% */;
		} catch (\Throwable $ʟ_e) {
			$this->global->cache->rollback();
			throw $ʟ_e;
		}
%A%
