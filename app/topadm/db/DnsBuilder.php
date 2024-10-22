<?php
namespace My\Topadm\Db;

class DnsBuilder {
    private string $dns_mysql = "mysql:host={host};port:{port};dbname={dbname};charset={charset}";
    private string $dns_pgsql = "pgsql:host={host};port:{port};dbname={dbname}";

    public function __construct(
        public string $db_type
    ){}

    public function createDNS(string $host, string $dbname, int $port, string $charset) : string 
    {
        $dns_tpl = match( $this->db_type ) {
            "mysql" => $this->dns_mysql,
            "pgsql" => $this->dns_pgsql
        };

        $result = "";
        $result = $this->bindingDNS($dns_tpl, [
            "host"   => $host,
            "dbname" => $dbname,
            "port"   => $port,
            "chrset" => $charset
        ]);

        return $result;
    }

    private function bindingDNS (string $tpl, array $dns_options) : string 
    {
        preg_match_all("/({+)(.*?)(})/", $tpl, $matches);
        $patterns = $matches[0];
        $columns  = $matches[2];

        # binding
        foreach($patterns as $idx => $text){
            $column_name = $columns[$idx];
            $render_args[$text] = (trim($dns_options[$column_name])) ? $dns_options[$column_name].' ':'';
        }
        return trim(strtr($tpl, $render_args));
    }
}
