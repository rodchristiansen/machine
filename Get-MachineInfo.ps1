# Get-MachineInfo.ps1
# Script to collect machine information for MunkiReport on Windows

function Get-MachineInfo {
    $computerSystem = Get-WmiObject Win32_ComputerSystem
    $operatingSystem = Get-WmiObject Win32_OperatingSystem
    $processor = Get-WmiObject Win32_Processor
    $bios = Get-WmiObject Win32_BIOS

    $machineInfo = [PSCustomObject]@{
        serial_number = $bios.SerialNumber
        hostname = $env:COMPUTERNAME
        machine_model = $computerSystem.Model
        machine_desc = $computerSystem.Description
        img_url = ""
        cpu = $processor.Name
        current_processor_speed = $processor.CurrentClockSpeed
        cpu_arch = $processor.Architecture
        os_version = $operatingSystem.Version
        physical_memory = [math]::Round($computerSystem.TotalPhysicalMemory / 1GB)
        platform_UUID = (Get-WmiObject Win32_ComputerSystemProduct).UUID
        number_processors = $computerSystem.NumberOfProcessors
        boot_rom_version = $bios.SMBIOSBIOSVersion
        bus_speed = ""
        computer_name = $computerSystem.Name
        l2_cache = $processor.L2CacheSize
        machine_name = $computerSystem.SystemFamily
        packages = (Get-WmiObject Win32_Product).Count
        buildversion = $operatingSystem.BuildNumber
    }

    return $machineInfo | ConvertTo-Json
}

# Run the function and output the result
Get-MachineInfo