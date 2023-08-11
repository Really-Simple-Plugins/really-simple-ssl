import * as React from "react";
const SvgWsSamoa = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="WS_-_Samoa_svg__a"
      width={16}
      height={12}
      x={0}
      y={0}
      maskUnits="userSpaceOnUse"
      style={{
        maskType: "luminance",
      }}
    >
      <path fill="#fff" d="M0 0h16v12H0z" />
    </mask>
    <g mask="url(#WS_-_Samoa_svg__a)">
      <path
        fill="#C51918"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="WS_-_Samoa_svg__b"
        width={16}
        height={12}
        x={0}
        y={0}
        maskUnits="userSpaceOnUse"
        style={{
          maskType: "luminance",
        }}
      >
        <path
          fill="#fff"
          fillRule="evenodd"
          d="M0 0v12h16V0H0Z"
          clipRule="evenodd"
        />
      </mask>
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#WS_-_Samoa_svg__b)">
        <path fill="#2E42A5" d="M0 0v7h8V0H0Z" />
        <path
          fill="#FEFFFF"
          d="m1.783 3.886-.53.32.12-.624-.44-.468.597-.025.253-.583.253.583h.596l-.44.493.133.624-.542-.32ZM5.783 3.886l-.53.32.12-.624-.44-.468.597-.025.253-.583.253.583h.596l-.44.493.133.624-.542-.32ZM3.733 2.069l-.499.301.114-.588-.416-.44.563-.023.238-.549.238.549h.561l-.414.463.125.588-.51-.301ZM4.273 4.213l-.312.188.071-.367-.26-.275.352-.015.15-.343.148.343h.35l-.258.29.078.367-.319-.188ZM3.704 6.414l-.748.452.17-.882-.622-.66.843-.035.357-.823.357.823h.843l-.622.695.187.882-.765-.452Z"
        />
      </g>
    </g>
  </svg>
);
export default SvgWsSamoa;
