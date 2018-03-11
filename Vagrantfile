$script = <<SCRIPT
#!/usr/bin/env bash

apt-get update
apt-get install -y php
apt-get install -y php-mbstring
apt-get install -y php-xml
apt-get install -y php-curl
apt-get install -y php-zip
apt-get install -y composer
SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "ubuntu/xenial64"
  config.vm.provider "virtualbox" do |vb|
    vb.customize [ "modifyvm", :id, "--uartmode1", "disconnected"]
  end
  config.vm.provision :shell, inline: $script
end
