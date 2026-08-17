<?php

namespace Shetabit\Sms\Tests\Feature;

use Illuminate\Support\Collection;
use Shetabit\Sms\Exceptions\DriverNotFoundException;
use Shetabit\Sms\Exceptions\InvalidMessageException;
use Shetabit\Sms\Sms;
use Shetabit\Sms\Tests\Fakes\FakeDriver;
use Shetabit\Sms\Tests\Fakes\NotADriver;

final class DriverResolutionTest extends FeatureTestCase
{
    public function testItStartsOnTheDefaultDriverOfTheConfiguration() : void
    {
        $this->fakeDriverIsConfigured();

        $this->assertSame('fake', $this->manager()->getDriver());
    }

    public function testItSendsThroughTheDriverOfTheConfiguration() : void
    {
        $this->fakeDriverIsConfigured();

        $this->manager()->to(['09120000001'])->message('the message')->send();

        $this->assertSame(
            [['recipient' => '09120000001', 'message' => 'the message', 'settings' => ['from' => '30001']]],
            FakeDriver::$sent
        );
    }

    public function testViaSwitchesTheDriverAndItsSettings() : void
    {
        $this->fakeDriverIsConfigured(asDefault: false);
        $this->register('other', FakeDriver::class, ['from' => '40002']);

        $manager = $this->manager()->via('other');

        $this->assertSame('other', $manager->getDriver());

        $manager->to(['09120000001'])->message('the message')->send();

        $this->assertSame(['from' => '40002'], FakeDriver::$sent[0]['settings']);
    }

    public function testConfigOverwritesTheSettingsOfTheCurrentDriver() : void
    {
        $this->fakeDriverIsConfigured();

        $this->manager()
            ->config('from', '50003')
            ->to(['09120000001'])
            ->message('the message')
            ->send();

        $this->assertSame(['from' => '50003'], FakeDriver::$sent[0]['settings']);
    }

    public function testConfigTakesSeveralSettingsAtOnce() : void
    {
        $this->fakeDriverIsConfigured();

        $this->manager()
            ->config(['from' => '50003', 'flash' => true])
            ->to(['09120000001'])
            ->message('the message')
            ->send();

        $this->assertSame(['from' => '50003', 'flash' => true], FakeDriver::$sent[0]['settings']);
    }

    public function testRecipientsIsAnotherNameForTo() : void
    {
        $this->fakeDriverIsConfigured();

        $this->manager()->recipients(['09120000001'])->message('the message')->send();

        $this->assertSame('09120000001', FakeDriver::$sent[0]['recipient']);
    }

    public function testASingleRecipientGetsTheBareAnswerOfTheDriver() : void
    {
        $this->fakeDriverIsConfigured();

        $answer = $this->manager()->to(['09120000001'])->message($this->message())->send();

        $this->assertIsArray($answer);
        $this->assertSame('09120000001', $answer['recipient']);
    }

    public function testSeveralRecipientsGetACollectionKeyedByRecipient() : void
    {
        $this->fakeDriverIsConfigured();

        $answer = $this->manager()
            ->to(['09120000001', '09120000002'])
            ->message($this->message())
            ->send();

        $this->assertInstanceOf(Collection::class, $answer);
        $this->assertSame(['09120000001', '09120000002'], $answer->keys()->all());
    }

    public function testItFailsWhenNoDefaultDriverIsConfigured() : void
    {
        config()->set('sms.default', '');

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not selected or default driver does not exist.');

        $this->manager();
    }

    public function testItFailsForADriverThatIsNotInTheConfiguration() : void
    {
        $this->fakeDriverIsConfigured();

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver not found in config file. Try updating the package.');

        $this->manager()->via('nowhere');
    }

    public function testItFailsForADriverWhoseClassIsMissing() : void
    {
        $this->fakeDriverIsConfigured();
        $this->register('ghost', 'Shetabit\Sms\Drivers\Ghost');

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage('Driver source not found. Please update the package.');

        $this->manager()->via('ghost');
    }

    public function testItFailsForAClassThatIsNotADriver() : void
    {
        $this->fakeDriverIsConfigured();
        $this->register('imposter', NotADriver::class);

        $this->expectException(DriverNotFoundException::class);
        $this->expectExceptionMessage(sprintf('Driver [%s] must be an instance of', NotADriver::class));

        $this->manager()->via('imposter');
    }

    public function testItFailsWhenNoMessageWasSet() : void
    {
        $this->fakeDriverIsConfigured();

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('Message not selected or does not exist.');

        $this->manager()->to(['09120000001'])->send();
    }

    public function testItTakesAMessageInstanceAsWellAsAString() : void
    {
        $this->fakeDriverIsConfigured();

        $manager = $this->manager()->to(['09120000001']);

        $this->assertInstanceOf(Sms::class, $manager->message($this->message('from an instance')));

        $manager->send();

        $this->assertSame('from an instance', FakeDriver::$sent[0]['message']);
    }
}
