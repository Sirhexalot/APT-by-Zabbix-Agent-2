# APT Update Monitoring for Zabbix Agent 2

A Zabbix template for monitoring package updates on Debian/Ubuntu systems with Zabbix Agent 2.

## Features

- 📊 **Available Security Updates**: Monitors the number of available security updates
- 📦 **Available Updates**: Counts the number of available regular updates
- ⏰ **Update History**: Tracks the timestamp of the last system update
- ⚠️ **Automatic Alerts**:
  - Alert for available security updates (HIGH)
  - Warning for too many pending updates
  - Warning if updates haven't been performed for too long

## Requirements

- **Zabbix Agent 2** (in active mode)
- **Debian/Ubuntu** based system
- `apt-get` package manager

## Installation

### 1. Agent Configuration

Add the UserParameter definitions to your Zabbix Agent 2 configuration:

#### Option A: Directly in `zabbix_agent2.conf`

```bash
# /etc/zabbix/zabbix_agent2.conf
UserParameter=apt.security,apt-get -s upgrade | grep -ci ^inst.*security | tr -d '\n'
UserParameter=apt.updates,apt-get -s upgrade | grep -iPc '^Inst((?!security).)*$' | tr -d '\n'
UserParameter=apt.last.update.timestamp,stat -c %Y /var/lib/apt/periodic/update-success-stamp 2>/dev/null || echo 0
UserParameter=apt.last.upgrade.timestamp,tac /var/log/apt/history.log* 2>/dev/null | awk '/^(Upgrade|Install):/ {found=1} found && /^End-Date:/ {gsub(/End-Date: /, ""); gsub(/  +/, " "); system("date -d \"" $0 "\" +%s"); exit}' 2>/dev/null || echo 0
```

#### Option B: In a separate file (recommended)

1. Create the UserParameters file:

```bash
# /etc/zabbix/zabbix_agent2.d/userparameters_updates.conf
UserParameter=apt.security,apt-get -s upgrade | grep -ci ^inst.*security | tr -d '\n'
UserParameter=apt.updates,apt-get -s upgrade | grep -iPc '^Inst((?!security).)*$' | tr -d '\n'
UserParameter=apt.last.update.timestamp,stat -c %Y /var/lib/apt/periodic/update-success-stamp 2>/dev/null || echo 0
UserParameter=apt.last.upgrade.timestamp,tac /var/log/apt/history.log* 2>/dev/null | awk '/^(Upgrade|Install):/ {found=1} found && /^End-Date:/ {gsub(/End-Date: /, ""); gsub(/  +/, " "); system("date -d \"" $0 "\" +%s"); exit}' 2>/dev/null || echo 0
```

2. Ensure the Include directive is enabled in `/etc/zabbix/zabbix_agent2.conf`:

```bash
# /etc/zabbix/zabbix_agent2.conf
Include=/etc/zabbix/zabbix_agent2.d/plugins.d/*.conf
```

### 2. Restart the Agent

```bash
sudo systemctl restart zabbix-agent2
```

### 3. Test the UserParameters

Before importing the template, verify that all UserParameters work correctly:

```bash
# Test security updates counter
sudo zabbix_agent2 -t apt.security

# Test regular updates counter
sudo zabbix_agent2 -t apt.updates

# Test last package list update timestamp (apt update)
sudo zabbix_agent2 -t apt.last.update.timestamp

# Test last package upgrade timestamp (apt upgrade/install)
sudo zabbix_agent2 -t apt.last.upgrade.timestamp
```

Expected output format:

```
apt.security                                  [t|3]
apt.updates                                   [t|12]
apt.last.update.timestamp                     [t|1738511234]
apt.last.upgrade.timestamp                    [t|1738398765]
```

**Note**: If `apt.last.upgrade.timestamp` returns `0`, it means no upgrades have been logged in `/var/log/apt/history.log*` yet.

### 4. Import Template into Zabbix

1. Navigate to **Administration → Templates** in your Zabbix instance
2. Click **Import**
3. Select the `zabbix_template_apt.yml` file
4. Configure the template macros (see below)
5. Link the template to your hosts

## Configuration

The template uses macros that you can customize:

| Macro                          | Default Value | Description                                                            |
| ------------------------------ | ------------- | ---------------------------------------------------------------------- |
| `{$WARN_DAYS}`                 | `30`          | Days after which a warning is issued if no updates have been performed |
| `{$MAX_NOT_INSTALLED_UPDATES}` | `5`           | Maximum number of pending updates before a warning is triggered        |

## Items

The template monitors the following metrics:

| Item                       | Key                          | Type      | Description                          |
| -------------------------- | ---------------------------- | --------- | ------------------------------------ |
| Available Security Updates | `apt.security`               | Active    | Number of available security updates |
| Available Updates          | `apt.updates`                | Active    | Number of available regular updates  |
| Last Update Timestamp      | `apt.last.update.timestamp`  | Active    | Unix timestamp of the last update    |
| Days Since Last Update     | `apt.days.since.last.update` | Dependent | Calculated number of days            |

## Triggers

The template automatically creates the following triggers:

1. **Security Updates Available** (HIGH)
   - Trigger: Security-relevant updates are available
   - Impact: Highest priority

2. **Too Many Updates Available** (WARNING)
   - Trigger: More than `{$MAX_NOT_INSTALLED_UPDATES}` updates pending
   - Recoverable: Manually

3. **No Updates Performed** (WARNING)
   - Trigger: Last update is older than `{$WARN_DAYS}` days
   - Recoverable: Manually

## Security

- ✅ The `apt-get -s` commands used **do not require root privileges**
- ✅ Can be safely executed under the Zabbix user
- ✅ Uses simulation mode only, no actual changes are made

## Troubleshooting

### Items show "No data"

1. Test the UserParameters manually:

   ```bash
   sudo zabbix_agent2 -t apt.security
   sudo zabbix_agent2 -t apt.updates
   sudo zabbix_agent2 -t apt.last.update.timestamp
   sudo zabbix_agent2 -t apt.last.upgrade.timestamp
   ```

2. Check agent logs: `journalctl -u zabbix-agent2 -f`

3. Verify the agent is in active mode and can reach the Zabbix server

4. Restart the agent: `sudo systemctl restart zabbix-agent2`

### Permission Errors

If the UserParameters are not being executed:

- Check the file permissions of the configuration file
- Ensure the Zabbix user can read the file

## File Structure

```
APT-by-Zabbix-Agent-2/
├── README.md                    # This document
├── zabbix_template_apt.yml      # Zabbix Template (Version 7.4+)
└── plugins.d/
    └── apt.conf                 # Agent UserParameter Configuration
```

## License

This project is licensed under the [MIT License](LICENSE).

## Support

For issues or questions, please open a GitHub issue.
