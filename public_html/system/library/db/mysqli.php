<?php
namespace DB;
final class MySQLi {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		// PHP 8.1 turned mysqli errors into exceptions by default. This class
		// checks errno/connect_error by hand, so restore the old behaviour.
		mysqli_report(MYSQLI_REPORT_OFF);

		// Managed database services (DigitalOcean, RDS) hand out a CA bundle and
		// expect a verified TLS connection. Point DB_SSL_CA at it to enable that;
		// with the constant unset this behaves exactly as it always has.
		$ca = defined('DB_SSL_CA') ? DB_SSL_CA : '';

		if ($ca) {
			$this->connection = mysqli_init();
			$this->connection->ssl_set(null, null, $ca, null, null);
			$this->connection->real_connect(
				$hostname, $username, $password, $database, (int)$port, null,
				MYSQLI_CLIENT_SSL
			);
		} else {
			$this->connection = new \mysqli($hostname, $username, $password, $database, $port);
		}

		if ($this->connection->connect_error) {
			throw new \Exception('Error: ' . $this->connection->error . '<br />Error No: ' . $this->connection->errno);
		}

		$this->connection->set_charset("utf8");
		$this->connection->query("SET SQL_MODE = ''");
	}

	public function query($sql) {
		$query = $this->connection->query($sql);

		if (!$this->connection->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();

				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;

				$query->close();

				return $result;
			} else {
				return true;
			}
		} else {
			throw new \Exception('Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
		}
	}

	public function escape($value) {
		return $this->connection->real_escape_string($value);
	}
	
	public function countAffected() {
		return $this->connection->affected_rows;
	}

	public function getLastId() {
		return $this->connection->insert_id;
	}
	
	public function connected() {
		return $this->connection->ping();
	}
	
	public function __destruct() {
		$this->connection->close();
	}
}
