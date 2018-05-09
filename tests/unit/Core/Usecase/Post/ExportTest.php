<?php
/**
 * Created by PhpStorm.
 * User: rowasc
 * Date: 5/2/18
 * Time: 1:36 PM
 */

namespace Tests\Unit\Core\Usecase\Post;

use Tests\TestCase;
use Mockery as M;
use Ushahidi\App\Repository\ExportJobRepository;
use Ushahidi\App\Repository\Form\AttributeRepository;
use Ushahidi\App\Repository\Post\ExportRepository;
use Ushahidi\Core\Entity\ExportJob;
use Ushahidi\Core\Entity\Post;
use Ushahidi\Core\SearchData;

class ExportTest extends TestCase
{
	public function setUp()
	{
		parent::setup();

		$this->postExportRepository = M::mock(ExportRepository::class);
		$this->exportJobRepository = M::mock(ExportJobRepository::class);
		$this->formAttributeRepository = M::mock(AttributeRepository::class);
		$this->usecase = service('factory.usecase')->get('posts_export', 'export');

		// Get the usecase and pass in authorizer, payload and transformer
		$this->usecase = $this->usecase
			->setAuthorizer(service('authorizer.export_job'))
			->setFormatter(service('formatter.entity.post.csv'));
		$this->usecase->setExportJobRepository($this->exportJobRepository);
		$this->usecase->setFormAttributeRepository($this->formAttributeRepository);
		$this->usecase->setPostExportRepository($this->postExportRepository);
	}

	public function tearDown()
	{
		parent::tearDown();
	}

	public function testJobIsUpdated()
	{
		// set CLI params to be the payload for the usecase
		$payload = [
			'job_id' => 1,
			'limit' => 100,
			'offset' => 0,
			'add_header' => true,
		];

		$post1 = new Post();
		$post2 = new Post();
		$post1->setState([
			'post_date' =>
				'2017-02-22'
		]);
		$post2->setState([
			'post_date' =>
				'2017-02-22'
		]);
		$jobRepoSpy = \Mockery::mock(ExportJobRepository::class);
		$searchDataMock = M::type(SearchData::class);

		$this->postExportRepository->shouldReceive('setSearchParams')
			->once()
			->with($searchDataMock)
			->andReturn(null);
		$this->postExportRepository->shouldReceive('retrieveMetaData')
			->twice()//once per post
			->andReturn([]);
		$this->postExportRepository->shouldReceive('getSearchResults')
			->once()
			->andReturn([$post1, $post2]);

		$jobRepoSpy
			->shouldReceive('setSearchParams')
			->with(['filters' => []])
			->andReturn(null);
		$this->formAttributeRepository->shouldReceive('getExportAttributes')->once()
			->with([])
			->andReturn([
				[
					'label' => 'Post ID',
					'key' => 'id',
					'type' => 'integer',
					'input' => 'number',
					'form_id' => 0,
					'form_stage_id' => 0,
					'form_stage_priority' => 0,
					'priority' => 1
				]]);

		$this->usecase
			->setExportJobRepository($jobRepoSpy);

		$exportJobEntity = new ExportJob();
		$exportJobEntity->setState([
			'user_id' => 1,
			'id'	=> 11,
			'post_date' => '2017-02-22',
		]);
		$jobRepoSpy
			->shouldReceive('get')
			->with(1)
			->andReturn($exportJobEntity);
		$jobRepoSpy->shouldReceive('update')
			->with($exportJobEntity)
			->andReturn(11);
		$this->usecase
			->setPayload($payload)
			->interact();
	}
}