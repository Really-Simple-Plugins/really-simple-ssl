import * as React from "react";
const SvgJoJordan = (props) => (
  <svg
    xmlns="http://www.w3.org/2000/svg"
    width={16}
    height={12}
    fill="none"
    {...props}
  >
    <mask
      id="JO_-_Jordan_svg__a"
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
    <g mask="url(#JO_-_Jordan_svg__a)">
      <path
        fill="#F7FCFF"
        fillRule="evenodd"
        d="M0 0v12h16V0H0Z"
        clipRule="evenodd"
      />
      <mask
        id="JO_-_Jordan_svg__b"
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
      <g fillRule="evenodd" clipRule="evenodd" mask="url(#JO_-_Jordan_svg__b)">
        <path fill="#272727" d="M0 0v4h16V0H0Z" />
        <path fill="#093" d="M0 8v4h16V8H0Z" />
      </g>
      <path
        fill="#E31D1C"
        fillRule="evenodd"
        d="M0 0v12l10-6L0 0Z"
        clipRule="evenodd"
      />
      <mask
        id="JO_-_Jordan_svg__c"
        width={10}
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
          d="M0 0v12l10-6L0 0Z"
          clipRule="evenodd"
        />
      </mask>
      <g mask="url(#JO_-_Jordan_svg__c)">
        <path
          fill="#F7FCFF"
          fillRule="evenodd"
          d="m4.5 6.935-.934.565.213-1.102L3 5.573l1.055-.044L4.5 4.5l.446 1.029H6l-.777.87.234 1.101-.956-.565Z"
          clipRule="evenodd"
        />
      </g>
    </g>
  </svg>
);
export default SvgJoJordan;
