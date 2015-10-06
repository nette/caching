<?php

/**
 * Test: Common tests for all IJournal implementations.
 */

use	Nette\Caching\Cache;
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
			Cache::TAGS => ['test:homepage'],
		]);

		Assert::same([
			'ok_test1',
		], $this->journal->clean([Cache::TAGS => ['test:homepage']]));
	}


	final public function testTwoTagsCleanOne()
	{
		$this->journal->write('ok_test2', [
			Cache::TAGS => ['test:homepage', 'test:homepage2'],
		]);

		Assert::same([
			'ok_test2',
		], $this->journal->clean([Cache::TAGS => ['test:homepage2']]));
	}


	final public function testTwoTagsCleanBoth()
	{
		$this->journal->write('ok_test2b', [
			Cache::TAGS => ['test:homepage', 'test:homepage2'],
		]);

		Assert::same([
			'ok_test2b',
		], $this->journal->clean([Cache::TAGS => ['test:homepage', 'test:homepage2']]));
	}


	final public function testTwoSameTags()
	{
		$this->journal->write('ok_test2c', [
			Cache::TAGS => ['test:homepage', 'test:homepage'],
		]);

		Assert::same([
			'ok_test2c',
		], $this->journal->clean([Cache::TAGS => ['test:homepage', 'test:homepage']]));
	}


	final public function testTagAndPriority()
	{
		$this->journal->write('ok_test2d', [
			Cache::TAGS => ['test:homepage'],
			Cache::PRIORITY => 15,
		]);

		Assert::same([
			'ok_test2d',
		], $this->journal->clean([Cache::TAGS => ['test:homepage'], Cache::PRIORITY => 20]));
	}


	final public function testPriorityOnly()
	{
		$this->journal->write('ok_test3', [
			Cache::PRIORITY => 10,
		]);

		Assert::same([
			'ok_test3',
		], $this->journal->clean([Cache::PRIORITY => 10]));
	}


	final public function testPriorityAndTagCleanByTag()
	{
		$this->journal->write('ok_test4', [
			Cache::TAGS => ['test:homepage'],
			Cache::PRIORITY => 10,
		]);

		Assert::same([
			'ok_test4',
		], $this->journal->clean([Cache::TAGS => ['test:homepage']]));
	}


	final public function testPriorityAndTagCleanByPriority()
	{
		$this->journal->write('ok_test5', [
			Cache::TAGS => ['test:homepage'],
			Cache::PRIORITY => 10,
		]);

		Assert::same([
			'ok_test5',
		], $this->journal->clean([Cache::PRIORITY => 10]));
	}


	final public function testDifferentCleaning()
	{
		for ($i = 1; $i <= 9; $i++) {
			$this->journal->write('ok_test6_' . $i, [
				Cache::TAGS => ['test:homepage', 'test:homepage/' . $i],
				Cache::PRIORITY => $i,
			]);
		}

		Assert::same([
			'ok_test6_1',
			'ok_test6_2',
			'ok_test6_3',
			'ok_test6_4',
			'ok_test6_5',
		], $this->journal->clean([Cache::PRIORITY => 5]));

		Assert::same([
			'ok_test6_7',
		], $this->journal->clean([Cache::TAGS => ['test:homepage/7']]));

		Assert::same([
		], $this->journal->clean([Cache::TAGS => ['test:homepage/4']]));

		Assert::same([
		], $this->journal->clean([Cache::PRIORITY => 4]));

		Assert::same([
			'ok_test6_6',
			'ok_test6_8',
			'ok_test6_9',
		], $this->journal->clean([Cache::TAGS => ['test:homepage']]));
	}


	final public function testSpecialChars()
	{
		$this->journal->write('ok_test7ščřžýáíé', [
			Cache::TAGS => ['čšřýýá', 'ýřžčýž']
		]);

		Assert::same([
			'ok_test7ščřžýáíé',
		], $this->journal->clean([Cache::TAGS => ['čšřýýá']]));
	}


	final public function testDuplicatedSameTags()
	{
		$this->journal->write('ok_test_a', [
			Cache::TAGS => ['homepage']
		]);
		$this->journal->write('ok_test_a', [
			Cache::TAGS => ['homepage']
		]);
		Assert::same([
			'ok_test_a',
		], $this->journal->clean([Cache::TAGS => ['homepage']]));
	}


	final public function testDuplicatedSamePriority()
	{
		$this->journal->write('ok_test_b', [
			Cache::PRIORITY => 12
		]);

		$this->journal->write('ok_test_b', [
			Cache::PRIORITY => 12
		]);

		Assert::same([
			'ok_test_b',
		], $this->journal->clean([Cache::PRIORITY => 12]));
	}


	final public function testDuplicatedDifferentTags()
	{
		$this->journal->write('ok_test_ba', [
			Cache::TAGS => ['homepage']
		]);

		$this->journal->write('ok_test_ba', [
			Cache::TAGS => ['homepage2']
		]);

		Assert::same([
		], $this->journal->clean([Cache::TAGS => ['homepage']]));

		Assert::same([
			'ok_test_ba',
		], $this->journal->clean([Cache::TAGS => ['homepage2']]));
	}


	final public function testDuplicatedTwoDifferentTags()
	{
		$this->journal->write('ok_test_baa', [
			Cache::TAGS => ['homepage', 'aąa']
		]);

		$this->journal->write('ok_test_baa', [
			Cache::TAGS => ['homepage2', 'aaa']
		]);

		Assert::same([
		], $this->journal->clean([Cache::TAGS => ['homepage']]));

		Assert::same([
			'ok_test_baa',
		], $this->journal->clean([Cache::TAGS => ['homepage2']]));
	}


	final public function testDuplicatedDifferentPriorities()
	{
		$this->journal->write('ok_test_bb', [
			Cache::PRIORITY => 15
		]);

		$this->journal->write('ok_test_bb', [
			Cache::PRIORITY => 20
		]);

		Assert::same([
			'ok_test_bb',
		], $this->journal->clean([Cache::PRIORITY => 30]));
	}


	final public function testCleanAll()
	{
		$this->journal->write('ok_test_all_tags', [
			Cache::TAGS => ['test:all', 'test:all']
		]);

		$this->journal->write('ok_test_all_priority', [
			Cache::PRIORITY => 5,
		]);

		Assert::null($this->journal->clean([Cache::ALL => TRUE]));
		Assert::same([
		], $this->journal->clean([Cache::TAGS => 'test:all']));
	}


	final public function testRemoveItemWithMultipleTags()
	{
		$this->journal->write('a', [Cache::TAGS => ['gamma']]);
		$this->journal->write('b', [Cache::TAGS => ['alpha', 'beta', 'gamma']]);

		$res = $this->journal->clean([Cache::TAGS => ['alpha', 'beta', 'gamma']]);
		sort($res);
		Assert::same([
			'a',
			'b',
		], $res);
	}

}
