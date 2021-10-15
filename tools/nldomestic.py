import asn1
import base45
import sys
from typing import List, Dict, Optional
from var_dump import var_dump
import json

# Thanks to the great work of Bart Wollf
# See his blog post:
# https://www.bartwolff.com/Blog/2021/08/21/decoding-the-dutch-domestic-coronacheck-qr-code

def asn1decode(d: asn1.Decoder):
    res = []
    while not d.eof():
        tag = d.peek()
        if tag.typ == asn1.Types.Primitive:
            tag, value = d.read()
            res.append(value)
        elif tag.typ == asn1.Types.Constructed:
            d.enter()
            res.append(asn1decode(d))
            d.leave()
    return res

class NLDomesticCovidCertSerialization:
    DisclosureTimeSeconds: int
    C: int
    A: int
    EResponse: int
    VResponse: int
    AResponse: int
    ADisclosed: List[int]

    def __init__(self, data: List):
        self.DisclosureTimeSeconds = data[0]
        self.C = data[1]
        self.A = data[2]
        self.EResponse = data[3]
        self.VResponse = data[4]
        self.AResponse = data[5]
        self.ADisclosed = data[6]

    def decode_metadata(self) -> List:
        b = NLDomesticCovidCertSerialization.decode_int(self.ADisclosed[0])
        d = asn1.Decoder()
        d.start(b)
        return asn1decode(d)[0]

    def decode_attributes(self) -> Dict[str, str]:
        res = {}
        attrs = ['isSpecimen',
            'isPaperProof',
            'validFrom',
            'validForHours',
            'firstNameInitial',
            'lastNameInitial',
            'birthDay',
            'birthMonth']
        for i, x in enumerate(self.ADisclosed[1:]):
            res[attrs[i]] = NLDomesticCovidCertSerialization.decode_int(x).decode('utf-8')
        return res

    @staticmethod
    def decode_int(value: int) -> Optional[bytes]:
        if not value & 1:
            return None
        else:
            v = value >> 1
            return v.to_bytes((v.bit_length() + 7) // 8, byteorder='big')

def main():
    with open("samples/bartwolff.txt") as handle:
        s = handle.readline().rstrip()

    b45data = s[4:]
    asn1data = base45.b58decode(b45data)

    decoder = asn1.Decoder()
    decoder.start(asn1data)

    obj = asn1decode(decoder)[0]

    annotator = NLDomesticCovidCertSerialization(obj)
    print("Version and public cert for verification:")
    print(annotator.decode_metadata())

    print("\nQR data")
    print(json.dumps(annotator.decode_attributes(), indent=4))

if __name__ == '__main__':
    main()