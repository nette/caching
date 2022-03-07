<?php

/**
 * Test: Common tests for all IJournal implementations.
 */

declare(strict_types=1);

use Nette\Caching\Cache;
use Nette\Caching\Storages\IJournal;
use Tester\Assert;


abstract class IJournalTestCase extends Tester\TestCase
{
	/** @var IJournal */
	private $journal;


	/** @return IJournal  */
	abstract public function createJournal();


	public function setup()
	{
		$this->journal = $this->createJournal();
	}


	final public function testOneTag()
	{
		$this->journal->write('ok_test1', [
			Cache::Tags => ['test:homepage'],
		]);

		Assert::same([
			'ok_test1',
		], $this->journal->clean([Cache::Tags => ['test:homepage']]));
	}


	final public function testTwoTagsCleanOne()
	{
		$this->journal->write('ok_test2', [
			Cache::Tags => ['test:homepage', 'test:homepage2'],
		]);

		Assert::same([
			'ok_test2',
		], $this->journal->clean([Cache::Tags => ['test:homepage2']]));
	}


	final public function testTwoTagsCleanBoth()
	{
		$this->journal->write('ok_test2b', [
			Cache::Tags => ['test:homepage', 'test:homepage2'],
		]);

		Assert::same([
			'ok_test2b',
		], $this->journal->clean([Cache::Tags => ['test:homepage', 'test:homepage2']]));
	}


	final public function testTwoSameTags()
	{
		$this->journal->write('ok_test2c', [
			Cache::Tags => ['test:homepage', 'test:homepage'],
		]);

		Assert::same([
			'ok_test2c',
		], $this->journal->clean([Cache::Tags => ['test:homepage', 'test:homepage']]));
	}


	final public function testTagAndPriority()
	{
		$this->journal->write('ok_test2d', [
			Cache::Tags => ['test:homepage'],
			Cache::Priority => 15,
		]);

		Assert::same([
			'ok_test2d',
		], $this->journal->clean([Cache::Tags => ['test:homepage'], Cache::Priority => 20]));
	}


	final public function testPriorityOnly()
	{
		$this->journal->write('ok_test3', [
			Cache::Priority => 10,
		]);

		Assert::same([
			'ok_test3',
		], $this->journal->clean([Cache::Priority => 10]));
	}


	final public function testPriorityAndTagCleanByTag()
	{
		$this->journal->write('ok_test4', [
			Cache::Tags => ['test:homepage'],
			Cache::Priority => 10,
		]);

		Assert::same([
			'ok_test4',
		], $this->journal->clean([Cache::Tags => ['test:homepage']]));
	}


	final public function testPriorityAndTagCleanByPriority()
	{
		$this->journal->write('ok_test5', [
			Cache::Tags => ['test:homepage'],
			Cache::Priority => 10,
		]);

		Assert::same([
			'ok_test5',
		], $this->journal->clean([Cache::Priority => 10]));
	}


	final public function testDifferentCleaning()
	{
		for ($i = 1; $i <= 9; $i++) {
			$this->journal->write('ok_test6_' . $i, [
				Cache::Tags => ['test:homepage', 'test:homepage/' . $i],
				Cache::Priority => $i,
			]);
		}

		Assert::same([
			'ok_test6_1',
			'ok_test6_2',
			'ok_test6_3',
			'ok_test6_4',
			'ok_test6_5',
		], $this->journal->clean([Cache::Priority => 5]));

		Assert::same([
			'ok_test6_7',
		], $this->journal->clean([Cache::Tags => ['test:homepage/7']]));

		Assert::same([
		], $this->journal->clean([Cache::Tags => ['test:homepage/4']]));

		Assert::same([
		], $this->journal->clean([Cache::Priority => 4]));

		Assert::same([
			'ok_test6_6',
			'ok_test6_8',
			'ok_test6_9',
		], $this->journal->clean([Cache::Tags => ['test:homepage']]));
	}


	final public function testSpecialChars()
	{
		$this->journal->write('ok_test7ščřžýáíé', [
			Cache::Tags => ['čšřýýá', 'ýřžčýž'],
		]);

		Assert::same([
			'ok_test7ščřžýáíé',
		], $this->journal->clean([Cache::Tags => ['čšřýýá']]));
	}


	final public function testDuplicatedSameTags()
	{
		$this->journal->write('ok_test_a', [
			Cache::Tags => ['homepage'],
		]);
		$this->journal->write('ok_test_a', [
			Cache::Tags => ['homepage'],
		]);
		Assert::same([
			'ok_test_a',
		], $this->journal->clean([Cache::Tags => ['homepage']]));
	}


	final public function testDuplicatedSamePriority()
	{
		$this->journal->write('ok_test_b', [
			Cache::Priority => 12,
		]);

		$this->journal->write('ok_test_b', [
			Cache::Priority => 12,
		]);

		Assert::same([
			'ok_test_b',
		], $this->journal->clean([Cache::Priority => 12]));
	}


	final public function testDuplicatedDifferentTags()
	{
		$this->journal->write('ok_test_ba', [
			Cache::Tags => ['homepage'],
		]);

		$this->journal->write('ok_test_ba', [
			Cache::Tags => ['homepage2'],
		]);

		Assert::same([
		], $this->journal->clean([Cache::Tags => ['homepage']]));

		Assert::same([
			'ok_test_ba',
		], $this->journal->clean([Cache::Tags => ['homepage2']]));
	}


	final public function testDuplicatedTwoDifferentTags()
	{
		$this->journal->write('ok_test_baa', [
			Cache::Tags => ['homepage', 'aąa'],
		]);

		$this->journal->write('ok_test_baa', [
			Cache::Tags => ['homepage2', 'aaa'],
		]);

		Assert::same([
		], $this->journal->clean([Cache::Tags => ['homepage']]));

		Assert::same([
			'ok_test_baa',
		], $this->journal->clean([Cache::Tags => ['homepage2']]));
	}


	final public function testDuplicatedDifferentPriorities()
	{
		$this->journal->write('ok_test_bb', [
			Cache::Priority => 10,
		]);

		$this->journal->write('ok_test_bb', [
			Cache::Priority => 20,
		]);

		Assert::same([
		], $this->journal->clean([Cache::Priority => 15]));

		Assert::same([
			'ok_test_bb',
		], $this->journal->clean([Cache::Priority => 30]));
	}


	final public function testCleanAll()
	{
		$this->journal->write('ok_test_all_tags', [
			Cache::Tags => ['test:all', 'test:all'],
		]);

		$this->journal->write('ok_test_all_priority', [
			Cache::Priority => 5,
		]);

		Assert::null($this->journal->clean([Cache::All => true]));
		Assert::same([
		], $this->journal->clean([Cache::Tags => ['test:all']]));
	}


	final public function testRemoveItemWithMultipleTags()
	{
		$this->journal->write('a', [Cache::Tags => ['gamma']]);
		$this->journal->write('b', [Cache::Tags => ['alpha', 'beta', 'gamma']]);

		$res = $this->journal->clean([Cache::Tags => ['alpha', 'beta', 'gamma']]);
		sort($res);
		Assert::same([
			'a',
			'b',
		], $res);
	}
}
