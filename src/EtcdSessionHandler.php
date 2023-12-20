<?php
namespace nix\etcdsh;

use Aternos\Etcd\ClientInterface;
use Aternos\Etcd\Exception\Status\InvalidResponseStatusCodeException;

class EtcdSessionHandler implements \SessionHandlerInterface
{
	private string $prefix = '';
	private array $leaseIDs = [];

	public function __construct(private ClientInterface $client)
	{}

    /** @noinspection PhpParameterNameChangedDuringInheritanceInspection */
    public function open(string $savePath, string $sessionName): bool
	{
		$this->prefix = "{$savePath}{$sessionName}/";
		return true;
	}

	public function close(): bool
	{
		return true;
	}

    /**
     * @throws InvalidResponseStatusCodeException
     */
    public function read(string $id): string
	{
		$getOp = $this->client->getGetOperation("{$this->prefix}{$id}:");
		$getReq = $getOp->getRequestRange();
		$getReq->setRangeEnd("{$this->prefix}{$id};");
		$getReq->setLimit(1);
		$txnResp = $this->client->txnRequest([$getOp], null, []);
		$getResp = $this->client->getResponses($txnResp)[/*op#*/0]['values'];
		if (isset($getResp[0], $getResp[0]['key']) && preg_match('/:([0-9]+)$/', $getResp[0]['key'], $matches, offset: strlen($this->prefix) + strlen($id)))
		{
			$this->leaseIDs[$id] = $matches[1];
			$this->client->refreshLease($this->leaseIDs[$id]);
			return $getResp[0]['value'];
		}
		else
		{
			$this->leaseIDs[$id] = null;
			return '';
		}
	}

    /**
     * @throws InvalidResponseStatusCodeException
     */
    public function write(string $id, string $data): bool
	{
		$leaseID = $this->leaseIDs[$id];
		if ($leaseID == null)
			$leaseID = $this->leaseIDs[$id] = $this->client->getLeaseID(intval(ini_get('session.gc_maxlifetime')));
		else
			$this->client->refreshLease($leaseID);
		$this->client->put("{$this->prefix}{$id}:$leaseID", $data, false, $leaseID);
		return true;
	}

    /**
     * @throws InvalidResponseStatusCodeException
     */
    public function destroy(string $id): bool
	{
		$op = $this->client->getDeleteOperation("{$this->prefix}{$id}:");
		$op->getRequestDeleteRange()->setRangeEnd("{$this->prefix}{$id};");
		$this->client->txnRequest([$op], null, []);
		return true;
	}

	public function gc(int $max_lifetime): int|bool
	{
		return false;
	}
}
