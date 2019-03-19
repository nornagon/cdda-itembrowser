# -*- mode: ruby -*-
# vi: set ft=ruby :

VAGRANTFILE_API_VERSION = "2"

Vagrant.configure(VAGRANTFILE_API_VERSION) do |config|
  config.vm.box = "ubuntu/trusty32"
  config.vm.hostname = "cataclysm"
  config.vm.synced_folder ".", "/vagrant", type: "nfs"
  config.vm.network :private_network, type: :dhcp
  config.vm.network :forwarded_port, guest: 80, host: 8000
  config.vm.provision "shell", path: "provision.sh"
  config.vm.provider :virtualbox do |v|
    v.customize ["modifyvm", :id, "--memory", 2048]
  end
end
