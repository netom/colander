#!/bin/bash

#
# Setup script for Travis CI server & local instalation of dependencies
# for running phpunit tests.
#

function die {
    echo $1
    exit 1
}

echo "Checking for composer.phar..."

if [[ -e composer.phar ]]; then
    echo "...found, skipping installation."
else
    echo "...not found, installing..."
    php -r "readfile('https://getcomposer.org/installer');" | php || \
        die "...failed to install composer.phar, aborting."
    echo "...done."
fi

echo "Checking for composer.lock..."

if [[ -e composer.lock ]]; then
    echo "...found, using packages defined in the lock file."
else
    echo "...no lock file was found, running update..."
    ./composer.phar update || \
        die "...failed to update dependencies, aboring."
    echo "...done."
fi

echo "Installing composer-defined components..."
./composer.phar install || \
    die "...failed to install composer components, aborintg."
echo "...done."

if php -r 'exit(class_exists("MongoDB") ? 0 : 1);'; then
    echo "The mongo extension is already installed, skipping installation."
else
    echo "Checking mongo extension version..."
    if [[ $MONGO_VERSION ]]; then
        echo "...\$MONGO_VERSION is defined, using $MONGO_VERSION."
    else
        "...\$MONGO_VERSION is not defined, using 1.6.6."
        MONGO_VERSION="1.6.6"
    fi

    if [[ $MONGO_VERSION == "mongofill-hhvm" ]]; then
        echo "Installing mongofill-hhvm..."

        mkdir BUILD
        cd BUILD

        # Well, well, well. We need a recent HHVM, travis MV has some ancient bug-ridden crap.
        export DEBIAN_FRONTEND=noninteractive
        sudo add-apt-repository -y ppa:ubuntu-toolchain-r/test || die "Could not add ppa:ubuntu-toolchain-r/test"
        sudo apt-get update -qy || die "apt-get update failed"
        sudo apt-get install -qy hhvm hhvm-dev g++-4.8 libboost-dev || die "Could not install packages"
        sudo update-alternatives --install /usr/bin/g++ g++ /usr/bin/g++-4.8 90 || die "Could not set g++-4.8 as the default compiler"

        # install libgoogle.log-dev
        wget http://launchpadlibrarian.net/80433359/libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
        sudo dpkg -i libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
        rm libgoogle-glog0_0.3.1-1ubuntu1_amd64.deb
        wget http://launchpadlibrarian.net/80433361/libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
        sudo dpkg -i libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb
        rm libgoogle-glog-dev_0.3.1-1ubuntu1_amd64.deb

        # install libjemalloc
        wget http://ubuntu.mirrors.tds.net/ubuntu/pool/universe/j/jemalloc/libjemalloc1_3.6.0-2_amd64.deb
        sudo dpkg -i libjemalloc1_3.6.0-2_amd64.deb
        rm libjemalloc1_3.6.0-2_amd64.deb
        wget http://ubuntu.mirrors.tds.net/ubuntu/pool/universe/j/jemalloc/libjemalloc-dev_3.6.0-2_amd64.deb
        sudo dpkg -i libjemalloc-dev_3.6.0-2_amd64.deb
        rm libjemalloc-dev_3.6.0-2_amd64.deb

        # compile libbson
        wget https://github.com/mongodb/libbson/archive/1.1.4.tar.gz
        tar xzf 1.1.4.tar.gz
        cd libbson-1.1.4
        ./autogen.sh
        make
        sudo make install
        cd ..

        # compile mongofill-hhvm
        git clone https://github.com/mongofill/mongofill-hhvm
        cd mongofill-hhvm
        git checkout -b branch-58c86c0a40b97d10ee6a7bb8c1e233d2f9c78420 58c86c0a40b97d10ee6a7bb8c1e233d2f9c78420
        ./build.sh || exit 1

        sudo mkdir /hhvm-extensions && sudo mv mongo.so /hhvm-extensions

        sudo echo "hhvm.dynamic_extension_path=/hhvm-extensions" >> /etc/hhvm/php.ini
        sudo echo "hhvm.dynamic_extensions[mongo]=mongo.so" >> /etc/hhvm/php.ini

        cd ../..

        command -v phpenv 2>&1 > /dev/null && phpenv rehash

        echo "ext-mongo version: `hhvm --php -r 'echo phpversion(\"mongo\");'`"

        echo "Checking 'unimplemented bson_encode' bug..."
        echo "`hhvm --php -r 'var_dump(function_exists(\"bson_encode\"));'`"
        hhvm --php -r 'function bson_encode($data) {return $data;} print bson_encode("Yes, I can create the missing function. And call it.\n");'
        echo $?
    elif [[ $MONGO_VERSION == "mongofill" ]]; then
        ./composer.phar require mongofill/mongofill=dev-master
        ./composer.phar install
    else
        yes '' | pecl install -f mongo-${MONGO_VERSION}

        command -v phpenv 2>&1 > /dev/null && phpenv rehash

        echo "ext-mongo version: `php -r 'echo phpversion(\"mongo\");'`"
    fi
fi
