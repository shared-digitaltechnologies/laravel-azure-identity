@description('The name of the application gateway.')
param applicationGatewayName string = 'single-domain-gateway'

@description('Location for all resources.')
param location string = resourceGroup().location

resource singleDomainGateway 'Microsoft.Network/applicationGateways@2021-05-01' = {
    name: applicationGatewayName
    location: location
    sku: {
        name: 'Standard_v2'
        tier: 'Standard_v2'
    }
    frontendIPConfigurations: [
        {
            name: 'appGwPublicFrontendIp'
            properties: {
                privateIPAllocationMethod: 'Dynamic',
                publicIpAddress: {
                    id: resourceId()
                }
            }
        }
    ]
    frontendPorts: [
        {
            name: 'port_80'
            properties: {
                port: 80
            }
        }
    ]
    backendAddressPools: [
        {
            name: 'backendPool'
            properties: {}
        }
    ]
    backendHttpSettingsCollection: [
        {
            name: 'myHTTPSetting',
            properties: {
                port: 80
                protocol: 'Http',
                cookieBasedAffinity: 'Disabled'
                pickHostNameFromBackendAddress: false
                requestTimeout: 20
            }
        }
    ]
}
