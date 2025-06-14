# EC2 Apache Logs Monitoring using Loki, Promtail, and Grafana

✅ Goal
To build a bank application on an Amazon EC2 instance with Apache web server, and monitor its logs using Loki, Promtail, and Grafana, including proper error troubleshooting.

## 🧾 Prerequisites

✅ EC2 instance (Amazon Linux 2 or 2023)

✅ Apache HTTPD installed (sudo yum install httpd -y)

✅ Apache running: sudo systemctl start httpd && sudo systemctl enable httpd

✅ Ports 22, 80, 3000 open in security group (optionally 3100, 9080)

## 🔧 Part 1: Install and Configure Loki
1. Download and install Loki binary
```python
cd ~
wget https://github.com/grafana/loki/releases/download/v2.9.4/loki-linux-amd64.zip
unzip loki-linux-amd64.zip
chmod +x loki-linux-amd64
sudo mv loki-linux-amd64 /usr/local/bin/loki)
```
2. Create Loki config file
```
sudo tee /etc/loki-config.yaml > /dev/null <<EOF
auth_enabled: false

server:
  http_listen_port: 3100
  grpc_listen_port: 9095

ingester:
  lifecycler:
    ring:
      kvstore:
        store: inmemory
      replication_factor: 1
  chunk_idle_period: 5m
  max_chunk_age: 1h
  chunk_retain_period: 30s
  max_transfer_retries: 0

schema_config:
  configs:
    - from: 2024-01-01
      store: boltdb-shipper
      object_store: filesystem
      schema: v13
      index:
        prefix: index_
        period: 24h

storage_config:
  boltdb_shipper:
    active_index_directory: /tmp/loki/index
    shared_store: filesystem
    cache_location: /tmp/loki/cache
  filesystem:
    directory: /tmp/loki/chunks

compactor:
  working_directory: /tmp/loki/compactor
  shared_store: filesystem

limits_config:
  enforce_metric_name: false
  reject_old_samples: true
  reject_old_samples_max_age: 168h

chunk_store_config:
  max_look_back_period: 0s

table_manager:
  retention_deletes_enabled: false
  retention_period: 0s
EOF
```
3. Create required directories
```
sudo mkdir -p /tmp/loki/{index,cache,chunks,compactor}
```
4. Start Loki
```
nohup loki -config.file=/etc/loki-config.yaml > /var/log/loki.log 2>&1 &
```
✅ Verify Loki is running
```
curl http://localhost:3100/ready
```
❗ If Error: mkdir : no such file or directory
Fix:
Make sure you created all necessary directories:
```
sudo mkdir -p /tmp/loki/{index,cache,chunks,compactor}
```

## 🔧 Part 2: Install and Configure Promtail
1. Download and install Promtail binary
```
cd ~
wget https://github.com/grafana/loki/releases/download/v2.9.4/promtail-linux-amd64.zip
unzip promtail-linux-amd64.zip
chmod +x promtail-linux-amd64
sudo mv promtail-linux-amd64 /usr/local/bin/promtail
```
2. Create Promtail config file
```
sudo tee /etc/promtail-config.yaml > /dev/null <<EOF
server:
  http_listen_port: 9080
  grpc_listen_port: 0

positions:
  filename: /tmp/promtail-positions.yaml

clients:
  - url: http://localhost:3100/loki/api/v1/push

scrape_configs:
  - job_name: apache-logs
    static_configs:
      - targets:
          - localhost
        labels:
          job: apache
          __path__: /var/log/httpd/access_log
EOF
```
📌 For Ubuntu: change __path__ to /var/log/apache2/access.log

3. Start Promtail
```
nohup promtail -config.file=/etc/promtail-config.yaml > /var/log/promtail.log 2>&1 &
```
📊 Part 3: Install and Connect Grafana
```
sudo yum install -y https://dl.grafana.com/oss/release/grafana-10.2.2-1.x86_64.rpm
sudo systemctl start grafana-server
sudo systemctl enable grafana-server
```

## 2. Access Grafana in browser
Open:
```
http://<your-ec2-public-ip>:3000
```
Login:
```
Username: admin
Password: admin (you will be asked to change)
```
3. Add Loki as data source

      GO to Settings → Data Sources

     Click Add data source

     Choose Loki

   Set URL to:
```
http://localhost:3100
```
Click Save & Test

## 🔍 Part 4: View Apache Logs

Go to Explore

Select Loki as the data source

Enter query:
```
{job="apache"}
```
Click Run Query

You will now see live Apache logs from your EC2 instance.

## 🚨 Troubleshooting Summary

| Problem | Cause | Fix |

|`mkdir: no such file or directory`                      | Missing Loki folders          | `sudo mkdir -p /tmp/loki/{index,cache,chunks,compactor}`|

| `schema v13 is required`                                | Outdated schema in config     | Use `schema: v13` in `loki-config.yaml`|

| `curl localhost:3100/ready` → not `ready`               | Loki crashed or not running   | Check logs: `cat /var/log/loki.log`|

| No logs showing in Grafana                              | Promtail misconfigured        | Check Promtail config + logs: `cat /var/log/promtail.log` |

| Apache log path not found | Distro uses different log path| Use `/var/log/httpd/access_log` (Amazon Linux) or `/var/log/apache2/access.log` (Ubuntu) |

| Grafana can't connect to Loki  | Wrong URL or Loki not running | Set URL to `http://localhost:3100` in Grafana Loki data source     |


DONE 😊
