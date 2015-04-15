<?php

/**
 * Test: Common tests for all IJournal implementations.
 */

use	Nette\Caching\Cache,
	Nette\Caching\Storages\IJournal,
	Tester\Assert;


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
		$this->journal->write('ok_test1', array(
			Cache::TAGS => array('test:homepage'),
		));

		Assert::same(array(
			'ok_test1',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage'))));
	}


	final public function testTwoTagsCleanOne()
	{
		$this->journal->write('ok_test2', array(
			Cache::TAGS => array('test:homepage', 'test:homepage2'),
		));

		Assert::same(array(
			'ok_test2',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage2'))));
	}


	final public function testTwoTagsCleanBoth()
	{
		$this->journal->write('ok_test2b', array(
			Cache::TAGS => array('test:homepage', 'test:homepage2'),
		));

		Assert::same(array(
			'ok_test2b',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage', 'test:homepage2'))));
	}


	final public function testTwoSameTags()
	{
		$this->journal->write('ok_test2c', array(
			Cache::TAGS => array('test:homepage', 'test:homepage'),
		));

		Assert::same(array(
			'ok_test2c',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage', 'test:homepage'))));
	}


	final public function testTagAndPriority()
	{
		$this->journal->write('ok_test2d', array(
			Cache::TAGS => array('test:homepage'),
			Cache::PRIORITY => 15,
		));

		Assert::same(array(
			'ok_test2d',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage'), Cache::PRIORITY => 20)));
	}


	final public function testPriorityOnly()
	{
		$this->journal->write('ok_test3', array(
			Cache::PRIORITY => 10,
		));

		Assert::same(array(
			'ok_test3',
		), $this->journal->clean(array(Cache::PRIORITY => 10)));
	}


	final public function testPriorityAndTagCleanByTag()
	{
		$this->journal->write('ok_test4', array(
			Cache::TAGS => array('test:homepage'),
			Cache::PRIORITY => 10,
		));

		Assert::same(array(
			'ok_test4',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage'))));
	}


	final public function testPriorityAndTagCleanByPriority()
	{
		$this->journal->write('ok_test5', array(
			Cache::TAGS => array('test:homepage'),
			Cache::PRIORITY => 10,
		));

		Assert::same(array(
			'ok_test5',
		), $this->journal->clean(array(Cache::PRIORITY => 10)));
	}


	final public function testDifferentCleaning()
	{
		for ($i = 1; $i <= 10; $i++) {
			$this->journal->write('ok_test6_' . $i, array(
				Cache::TAGS => array('test:homepage', 'test:homepage/' . $i),
				Cache::PRIORITY => $i,
			));
		}

		Assert::same(array(
			'ok_test6_1',
			'ok_test6_2',
			'ok_test6_3',
			'ok_test6_4',
			'ok_test6_5',
		), $this->journal->clean(array(Cache::PRIORITY => 5)));

		Assert::same(array(
			'ok_test6_7',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage/7'))));

		Assert::same(array(
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage/4'))));

		Assert::same(array(
		), $this->journal->clean(array(Cache::PRIORITY => 4)));

		Assert::same(array(
			'ok_test6_6',
			'ok_test6_8',
			'ok_test6_9',
			'ok_test6_10',
		), $this->journal->clean(array(Cache::TAGS => array('test:homepage'))));
	}


	final public function testSpecialChars()
	{
		$this->journal->write('ok_test7ščřžýáíé', array(
			Cache::TAGS => array('čšřýýá', 'ýřžčýž')
		));

		Assert::same(array(
			'ok_test7ščřžýáíé',
		), $this->journal->clean(array(Cache::TAGS => array('čšřýýá'))));
	}


	final public function testDuplicatedSameTags()
	{
		$this->journal->write('ok_test_a', array(
			Cache::TAGS => array('homepage')
		));
		$this->journal->write('ok_test_a', array(
			Cache::TAGS => array('homepage')
		));
		Assert::same(array(
			'ok_test_a',
		), $this->journal->clean(array(Cache::TAGS => array('homepage'))));
	}


	final public function testDuplicatedSamePriority()
	{
		$this->journal->write('ok_test_b', array(
			Cache::PRIORITY => 12
		));

		$this->journal->write('ok_test_b', array(
			Cache::PRIORITY => 12
		));

		Assert::same(array(
			'ok_test_b',
		), $this->journal->clean(array(Cache::PRIORITY => 12)));
	}


	final public function testDuplicatedDifferentTags()
	{
		$this->journal->write('ok_test_ba', array(
			Cache::TAGS => array('homepage')
		));

		$this->journal->write('ok_test_ba', array(
			Cache::TAGS => array('homepage2')
		));

		Assert::same(array(
		), $this->journal->clean(array(Cache::TAGS => array('homepage'))));

		Assert::same(array(
			'ok_test_ba',
		), $this->journal->clean(array(Cache::TAGS => array('homepage2'))));
	}


	final public function testDuplicatedTwoDifferentTags()
	{
		$this->journal->write('ok_test_baa', array(
			Cache::TAGS => array('homepage', 'aąa')
		));

		$this->journal->write('ok_test_baa', array(
			Cache::TAGS => array('homepage2', 'aaa')
		));

		Assert::same(array(
		), $this->journal->clean(array(Cache::TAGS => array('homepage'))));

		Assert::same(array(
			'ok_test_baa',
		), $this->journal->clean(array(Cache::TAGS => array('homepage2'))));
	}


	final public function testDuplicatedDifferentPriorities()
	{
		$this->journal->write('ok_test_bb', array(
			Cache::PRIORITY => 15
		));

		$this->journal->write('ok_test_bb', array(
			Cache::PRIORITY => 20
		));

		Assert::same(array(
			'ok_test_bb',
		), $this->journal->clean(array(Cache::PRIORITY => 30)));
	}


	final public function testCleanAll()
	{
		$this->journal->write('ok_test_all_tags', array(
			Cache::TAGS => array('test:all', 'test:all')
		));

		$this->journal->write('ok_test_all_priority', array(
			Cache::PRIORITY => 5,
		));

		Assert::null($this->journal->clean(array(Cache::ALL => TRUE)));
		Assert::same(array(
		), $this->journal->clean(array(Cache::TAGS => 'test:all')));
	}


	final public function testRemoveItemWithMultipleTags()
	{
		$this->journal->write('a', array(Cache::TAGS => array('gamma')));
		$this->journal->write('b', array(Cache::TAGS => array('alpha', 'beta', 'gamma')));
		Assert::same(array(
			'b',
			'a',
		), $this->journal->clean(array(Cache::TAGS => array('alpha', 'beta', 'gamma'))));
	}

}
